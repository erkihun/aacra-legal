<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Actions\Requester\SubmitPortalAdvisoryRequestAction;
use App\Actions\Requester\ResolveDefaultRequesterLetterTemplateAction;
use App\Enums\AdvisoryRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requester\StoreAdvisoryRequestRequest;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\RequesterAccount;
use App\Support\RequestLetterTemplateData;
use App\Support\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryRequestController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(AdvisoryRequestStatus::cases(), 'value'))],
        ]);

        $requests = AdvisoryRequest::query()
            ->with(['category'])
            ->where('requester_account_id', $requester->getKey())
            ->when($filters['search'] ?? null, fn ($q, string $search) => $q->where(function ($b) use ($search) {
                $b->where('request_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('status', $status))
            ->latest('date_submitted')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requester/Advisory/Index', [
            'filters' => $filters,
            'requests' => $requests->through(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'subject' => $r->subject,
                'status' => $r->status?->value,
                'date_submitted' => $r->date_submitted?->toDateString(),
                'category' => ['name_en' => $r->category?->name_en, 'name_am' => $r->category?->name_am],
            ]),
            'statusOptions' => collect(AdvisoryRequestStatus::cases())->map(fn ($c) => [
                'label' => __("status.{$c->value}"),
                'value' => $c->value,
            ]),
        ]);
    }

    public function create(Request $request, ResolveDefaultRequesterLetterTemplateAction $resolver, RequestLetterTemplateData $templateData): Response|RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        try {
            $defaultTemplate = $resolver->execute();
        } catch (RuntimeException $exception) {
            return to_route('requester.dashboard')->with('error', $exception->getMessage());
        }

        return Inertia::render('Requester/Advisory/Create', [
            'categories' => AdvisoryCategory::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'name_am']),
            'requestingDepartment' => [
                'id' => $requester->department?->getKey(),
                'name_en' => $requester->department?->name_en,
                'name_am' => $requester->department?->name_am,
            ],
            'defaultTemplate' => $templateData->previewPayload(template: $defaultTemplate),
        ]);
    }

    public function store(StoreAdvisoryRequestRequest $request, SubmitPortalAdvisoryRequestAction $action): RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        $advisoryRequest = $action->execute($request->validated(), $requester);

        return redirect()->route('requester.advisory.show', $advisoryRequest)
            ->with('success', __('requester.advisory_submitted'));
    }

    public function show(Request $request, AdvisoryRequest $advisoryRequest, RequestLetterTemplateData $templateData): Response
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($advisoryRequest->requester_account_id === $requester->getKey(), 403);

        $advisoryRequest->load(['category', 'attachments', 'responses', 'department', 'letterTemplate']);

        $canEdit = $advisoryRequest->status === AdvisoryRequestStatus::RETURNED
            || $advisoryRequest->status === AdvisoryRequestStatus::SUBMITTED;

        return Inertia::render('Requester/Advisory/Show', [
            'requestItem' => [
                'id' => $advisoryRequest->id,
                'request_number' => $advisoryRequest->request_number,
                'subject' => $advisoryRequest->subject,
                'description' => $advisoryRequest->description,
                'formal_letter' => $templateData->renderPayload(
                    is_array($advisoryRequest->letter_snapshot) ? $advisoryRequest->letter_snapshot : null,
                    $advisoryRequest->letterTemplate,
                    (string) ($advisoryRequest->description ?? ''),
                    $advisoryRequest->request_number,
                    $advisoryRequest->subject,
                    $advisoryRequest->date_submitted?->toDateString(),
                    [
                        'name_en' => $advisoryRequest->department?->name_en,
                        'name_am' => $advisoryRequest->department?->name_am,
                    ],
                    app()->getLocale(),
                ),
                'status' => $advisoryRequest->status?->value,
                'date_submitted' => $advisoryRequest->date_submitted?->toDateString(),
                'category' => [
                    'name_en' => $advisoryRequest->category?->name_en,
                    'name_am' => $advisoryRequest->category?->name_am,
                ],
                'attachments' => $advisoryRequest->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'download_url' => route('attachments.download', $a),
                ]),
                'responses' => $advisoryRequest->responses->map(fn ($r) => [
                    'id' => $r->id,
                    'subject' => $r->subject,
                    'response' => $r->response,
                    'created_at' => $r->created_at?->toDateTimeString(),
                ]),
                'can_edit' => $canEdit,
            ],
        ]);
    }

    public function edit(
        Request $request,
        AdvisoryRequest $advisoryRequest,
        ResolveDefaultRequesterLetterTemplateAction $resolver,
        RequestLetterTemplateData $templateData,
    ): Response|RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($advisoryRequest->requester_account_id === $requester->getKey(), 403);
        abort_unless(
            in_array($advisoryRequest->status, [AdvisoryRequestStatus::SUBMITTED, AdvisoryRequestStatus::RETURNED], true),
            403,
        );

        $advisoryRequest->load(['category', 'letterTemplate', 'department']);

        try {
            $defaultTemplate = $advisoryRequest->letter_snapshot !== null || $advisoryRequest->letterTemplate !== null
                ? null
                : $resolver->execute();
        } catch (RuntimeException $exception) {
            return to_route('requester.advisory.show', $advisoryRequest)->with('error', $exception->getMessage());
        }

        return Inertia::render('Requester/Advisory/Create', [
            'requestItem' => [
                'id' => $advisoryRequest->id,
                'category_id' => $advisoryRequest->category_id,
                'subject' => $advisoryRequest->subject,
                'description' => $advisoryRequest->description,
            ],
            'categories' => AdvisoryCategory::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'name_am']),
            'requestingDepartment' => [
                'id' => $advisoryRequest->department?->getKey(),
                'name_en' => $advisoryRequest->department?->name_en,
                'name_am' => $advisoryRequest->department?->name_am,
            ],
            'defaultTemplate' => $templateData->previewPayload(
                is_array($advisoryRequest->letter_snapshot) ? $advisoryRequest->letter_snapshot : null,
                $advisoryRequest->letterTemplate ?? $defaultTemplate,
            ),
            'mode' => 'edit',
        ]);
    }

    public function update(
        StoreAdvisoryRequestRequest $request,
        AdvisoryRequest $advisoryRequest,
        ResolveDefaultRequesterLetterTemplateAction $resolver,
        RequestLetterTemplateData $templateData,
        RichTextSanitizer $richTextSanitizer,
    ): RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($advisoryRequest->requester_account_id === $requester->getKey(), 403);
        abort_unless(
            in_array($advisoryRequest->status, [AdvisoryRequestStatus::SUBMITTED, AdvisoryRequestStatus::RETURNED], true),
            403,
        );

        $validated = $request->validated();

        try {
            $template = $advisoryRequest->letterTemplate ?? $resolver->execute();
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $advisoryRequest->update([
            'category_id' => $validated['category_id'],
            'subject' => $validated['subject'],
            'description' => $richTextSanitizer->sanitize((string) ($validated['description'] ?? '')),
            'letter_template_id' => $advisoryRequest->letter_template_id ?? $template->getKey(),
            'letter_snapshot' => is_array($advisoryRequest->letter_snapshot) && $advisoryRequest->letter_snapshot !== []
                ? $advisoryRequest->letter_snapshot
                : $templateData->snapshot($template),
            'status' => AdvisoryRequestStatus::SUBMITTED,
        ]);

        return redirect()->route('requester.advisory.show', $advisoryRequest)
            ->with('success', __('requester.advisory_updated'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Actions\Requester\SubmitPortalLawsuitRequestAction;
use App\Actions\Requester\ResolveDefaultRequesterLetterTemplateAction;
use App\Enums\LawsuitRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requester\StoreLawsuitRequestRequest;
use App\Models\LawsuitFilingRequest;
use App\Models\RequesterAccount;
use App\Support\RequestLetterTemplateData;
use App\Support\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LawsuitRequestController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(LawsuitRequestStatus::cases(), 'value'))],
        ]);

        $requests = LawsuitFilingRequest::query()
            ->where('requester_account_id', $requester->getKey())
            ->when($filters['search'] ?? null, fn ($q, string $search) => $q->where(function ($b) use ($search) {
                $b->where('request_code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('status', $status))
            ->latest('date_submitted')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requester/Lawsuit/Index', [
            'filters' => $filters,
            'requests' => $requests->through(fn ($r) => [
                'id' => $r->id,
                'request_code' => $r->request_code,
                'subject' => $r->subject,
                'status' => $r->status?->value,
                'date_submitted' => $r->date_submitted?->toDateString(),
            ]),
            'statusOptions' => collect(LawsuitRequestStatus::cases())->map(fn ($c) => [
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

        return Inertia::render('Requester/Lawsuit/Create', [
            'requestingDepartment' => [
                'id' => $requester->department?->getKey(),
                'name_en' => $requester->department?->name_en,
                'name_am' => $requester->department?->name_am,
            ],
            'defaultTemplate' => $templateData->previewPayload(template: $defaultTemplate),
        ]);
    }

    public function store(StoreLawsuitRequestRequest $request, SubmitPortalLawsuitRequestAction $action): RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        $lawsuitRequest = $action->execute($request->validated(), $requester);

        return redirect()->route('requester.lawsuit-requests.show', $lawsuitRequest)
            ->with('success', __('requester.lawsuit_submitted'));
    }

    public function show(Request $request, LawsuitFilingRequest $lawsuitRequest, RequestLetterTemplateData $templateData): Response
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($lawsuitRequest->requester_account_id === $requester->getKey(), 403);

        $lawsuitRequest->load(['attachments', 'requestingDepartment', 'letterTemplate']);

        $canEdit = in_array($lawsuitRequest->status, [
            LawsuitRequestStatus::SUBMITTED,
            LawsuitRequestStatus::RETURNED,
        ], true);

        return Inertia::render('Requester/Lawsuit/Show', [
            'requestItem' => [
                'id' => $lawsuitRequest->id,
                'request_code' => $lawsuitRequest->request_code,
                'subject' => $lawsuitRequest->subject,
                'description' => $lawsuitRequest->description,
                'formal_letter' => $templateData->renderPayload(
                    is_array($lawsuitRequest->letter_snapshot) ? $lawsuitRequest->letter_snapshot : null,
                    $lawsuitRequest->letterTemplate,
                    (string) ($lawsuitRequest->description ?? ''),
                    $lawsuitRequest->request_code,
                    $lawsuitRequest->subject,
                    $lawsuitRequest->date_submitted?->toDateString(),
                    [
                        'name_en' => $lawsuitRequest->requestingDepartment?->name_en,
                        'name_am' => $lawsuitRequest->requestingDepartment?->name_am,
                    ],
                    app()->getLocale(),
                ),
                'status' => $lawsuitRequest->status?->value,
                'reviewer_notes' => $lawsuitRequest->reviewer_notes,
                'date_submitted' => $lawsuitRequest->date_submitted?->toDateString(),
                'attachments' => $lawsuitRequest->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'original_name' => $a->original_name,
                    'download_url' => route('attachments.download', $a),
                ]),
                'can_edit' => $canEdit,
            ],
        ]);
    }

    public function edit(
        Request $request,
        LawsuitFilingRequest $lawsuitRequest,
        ResolveDefaultRequesterLetterTemplateAction $resolver,
        RequestLetterTemplateData $templateData,
    ): Response|RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($lawsuitRequest->requester_account_id === $requester->getKey(), 403);
        abort_unless(
            in_array($lawsuitRequest->status, [LawsuitRequestStatus::SUBMITTED, LawsuitRequestStatus::RETURNED], true),
            403,
        );

        $lawsuitRequest->load(['letterTemplate', 'requestingDepartment']);

        try {
            $defaultTemplate = $lawsuitRequest->letter_snapshot !== null || $lawsuitRequest->letterTemplate !== null
                ? null
                : $resolver->execute();
        } catch (RuntimeException $exception) {
            return to_route('requester.lawsuit-requests.show', $lawsuitRequest)->with('error', $exception->getMessage());
        }

        return Inertia::render('Requester/Lawsuit/Create', [
            'requestItem' => [
                'id' => $lawsuitRequest->id,
                'subject' => $lawsuitRequest->subject,
                'description' => $lawsuitRequest->description,
            ],
            'requestingDepartment' => [
                'id' => $lawsuitRequest->requestingDepartment?->getKey(),
                'name_en' => $lawsuitRequest->requestingDepartment?->name_en,
                'name_am' => $lawsuitRequest->requestingDepartment?->name_am,
            ],
            'defaultTemplate' => $templateData->previewPayload(
                is_array($lawsuitRequest->letter_snapshot) ? $lawsuitRequest->letter_snapshot : null,
                $lawsuitRequest->letterTemplate ?? $defaultTemplate,
            ),
            'mode' => 'edit',
        ]);
    }

    public function update(StoreLawsuitRequestRequest $request, LawsuitFilingRequest $lawsuitRequest): RedirectResponse
    {
        /** @var RequesterAccount $requester */
        $requester = $request->user('requester');

        abort_unless($lawsuitRequest->requester_account_id === $requester->getKey(), 403);
        abort_unless(
            in_array($lawsuitRequest->status, [LawsuitRequestStatus::SUBMITTED, LawsuitRequestStatus::RETURNED], true),
            403,
        );

        $validated = $request->validated();

        try {
            $template = $lawsuitRequest->letterTemplate ?? app(ResolveDefaultRequesterLetterTemplateAction::class)->execute();
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $lawsuitRequest->update([
            'subject' => $validated['subject'],
            'description' => app(RichTextSanitizer::class)->sanitize((string) ($validated['description'] ?? '')),
            'letter_template_id' => $lawsuitRequest->letter_template_id ?? $template->getKey(),
            'letter_snapshot' => is_array($lawsuitRequest->letter_snapshot) && $lawsuitRequest->letter_snapshot !== []
                ? $lawsuitRequest->letter_snapshot
                : app(RequestLetterTemplateData::class)->snapshot($template),
            'status' => LawsuitRequestStatus::SUBMITTED,
        ]);

        return redirect()->route('requester.lawsuit-requests.show', $lawsuitRequest)
            ->with('success', __('requester.lawsuit_updated'));
    }
}

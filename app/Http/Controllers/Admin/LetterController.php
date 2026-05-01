<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ApproveLetterAction;
use App\Actions\GenerateLetterReferenceNumberAction;
use App\Actions\PersistLetterAction;
use App\Actions\RenderLetterPdfAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveLetterRequest;
use App\Http\Requests\Admin\LetterRequest;
use App\Models\Department;
use App\Models\Letter;
use App\Models\LetterTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LetterController extends Controller
{
    public function __construct(
        private readonly PersistLetterAction $persistLetter,
        private readonly ApproveLetterAction $approveLetter,
        private readonly GenerateLetterReferenceNumberAction $generateReferenceNumber,
        private readonly RenderLetterPdfAction $renderLetterPdf,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Letter::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,final,archived'],
            'template_id' => ['nullable', 'uuid'],
        ]);

        $letters = Letter::query()
            ->with(['template'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_organization', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['template_id'] ?? null, fn ($query, string $templateId) => $query->where('template_id', $templateId))
            ->latest('letter_date')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Letter $letter): array => [
                'id' => $letter->id,
                'reference_number' => $letter->reference_number,
                'subject' => $letter->subject,
                'recipient_name' => $letter->recipient_name,
                'template_name' => $letter->template?->name,
                'template_id' => $letter->template_id,
                'letter_date' => $letter->letter_date?->toDateString(),
                'status' => $letter->status,
                'can' => [
                    'update' => $request->user()?->can('update', $letter) ?? false,
                    'delete' => $request->user()?->can('delete', $letter) ?? false,
                    'preview' => $request->user()?->can('preview', $letter) ?? false,
                    'print' => $request->user()?->can('print', $letter) ?? false,
                    'download' => $request->user()?->can('preview', $letter) ?? false,
                    'approve' => $request->user()?->can('approve', $letter) ?? false,
                ],
            ]);

        return Inertia::render('Admin/Letters/Index', [
            'filters' => $filters,
            'letters' => $letters,
            'templates' => $this->templateOptions(),
            'can' => [
                'create' => $request->user()?->can('create', Letter::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize('create', Letter::class);

        $selectedTemplate = $this->resolveTemplateFromRequest($request);

        return Inertia::render('Admin/Letters/Form', [
            'letterItem' => null,
            'selectedTemplate' => $selectedTemplate ? $this->templateSelectionPayload($selectedTemplate) : null,
            'templateOptions' => $this->templateOptions(),
            'departments' => $this->departmentOptions(),
            'canDelete' => false,
        ]);
    }

    public function store(LetterRequest $request): RedirectResponse
    {
        $letter = $this->persistLetter->execute(
            $this->payload($request),
            $request->user() ?? abort(403),
        );

        return to_route('letters.show', $letter)
            ->with('success', __('letters.flash.created'));
    }

    public function show(Letter $letter): InertiaResponse
    {
        $this->authorize('view', $letter);

        return Inertia::render('Admin/Letters/Show', [
            'letterItem' => $this->letterPayload($letter),
            'can' => [
                'update' => request()->user()?->can('update', $letter) ?? false,
                'delete' => request()->user()?->can('delete', $letter) ?? false,
                'preview' => request()->user()?->can('preview', $letter) ?? false,
                'print' => request()->user()?->can('print', $letter) ?? false,
                'download' => request()->user()?->can('preview', $letter) ?? false,
                'approve' => request()->user()?->can('approve', $letter) ?? false,
            ],
        ]);
    }

    public function edit(Letter $letter): InertiaResponse
    {
        $this->authorize('update', $letter);

        $template = $letter->template;

        return Inertia::render('Admin/Letters/Form', [
            'letterItem' => $this->letterPayload($letter),
            'selectedTemplate' => $template ? $this->templateSelectionPayload($template) : null,
            'templateOptions' => $this->templateOptions($letter->template_id),
            'departments' => $this->departmentOptions(),
            'canDelete' => request()->user()?->can('delete', $letter) ?? false,
        ]);
    }

    public function update(LetterRequest $request, Letter $letter): RedirectResponse
    {
        $letter = $this->persistLetter->execute(
            $this->payload($request, false),
            $request->user() ?? abort(403),
            $letter,
        );

        return to_route('letters.show', $letter)
            ->with('success', __('letters.flash.updated'));
    }

    public function approve(ApproveLetterRequest $request, Letter $letter): RedirectResponse
    {
        $letter = $this->approveLetter->execute(
            $letter,
            $request->user() ?? abort(403),
        );

        return to_route('letters.show', $letter)
            ->with('success', __('letters.flash.approved'));
    }

    public function destroy(Letter $letter): RedirectResponse
    {
        $this->authorize('delete', $letter);

        $letter->delete();

        return to_route('letters.index')
            ->with('success', __('letters.flash.deleted'));
    }

    public function preview(Letter $letter): InertiaResponse
    {
        $this->authorize('preview', $letter);

        return Inertia::render('Admin/Letters/Preview', [
            'letterItem' => $this->letterPayload($letter),
            'pdfUrl' => route('letters.pdf', $letter),
            'downloadUrl' => route('letters.download-pdf', $letter),
            'printUrl' => request()->user()?->can('print', $letter) ? route('letters.print', $letter) : null,
            'canPrint' => request()->user()?->can('print', $letter) ?? false,
        ]);
    }

    public function pdf(Letter $letter): Response
    {
        $this->authorize('preview', $letter);

        return $this->renderLetterPdf->inlineResponse($letter);
    }

    public function print(Letter $letter): Response
    {
        $this->authorize('print', $letter);

        return $this->renderLetterPdf->inlineResponse($letter);
    }

    public function downloadPdf(Letter $letter): Response
    {
        $this->authorize('preview', $letter);

        return $this->renderLetterPdf->downloadResponse($letter);
    }

    private function resolveTemplateFromRequest(Request $request): ?LetterTemplate
    {
        $templateId = $request->query('template_id');

        if (! is_string($templateId) || $templateId === '') {
            return null;
        }

        return LetterTemplate::query()
            ->whereKey($templateId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templateOptions(?string $selectedTemplateId = null): array
    {
        return LetterTemplate::query()
            ->when($selectedTemplateId, function ($query, string $templateId): void {
                $query->withTrashed();
                $query->where(function ($builder) use ($templateId): void {
                    $builder->where('is_active', true)->orWhere('id', $templateId);
                });
            }, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(fn (LetterTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'code' => $template->code,
                'language' => $template->language,
                'reference_prefix' => $template->reference_prefix,
                'is_active' => $template->is_active,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function departmentOptions(): array
    {
        return Department::query()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_am', 'is_active'])
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name_en' => $department->name_en,
                'name_am' => $department->name_am,
                'is_active' => $department->is_active,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function templateSelectionPayload(LetterTemplate $template): array
    {
        $numberingConfig = is_array($template->numbering_config) ? $template->numbering_config : [];

        return [
            'id' => $template->id,
            'name' => $template->name,
            'code' => $template->code,
            'language' => $template->language,
            'page_size' => $template->page_size,
            'orientation' => $template->orientation,
            'reference_label' => $template->reference_label,
            'reference_prefix' => $template->reference_prefix,
            'reference_start_number' => $template->reference_start_number,
            'current_reference_number' => $template->current_reference_number,
            'numbering_config' => [
                'separator' => $numberingConfig['separator'] ?? '/',
                'include_year' => (bool) ($numberingConfig['include_year'] ?? true),
                'pad_length' => (int) ($numberingConfig['pad_length'] ?? 4),
            ],
            'subject_template' => $template->subject_template,
            'recipient_block_template' => $template->recipient_block_template,
            'salutation_template' => $template->salutation_template,
            'body_content' => $template->body_content,
            'closing_content' => $template->closing_content,
            'signature_block_content' => $template->signature_block_content,
            'cc_content' => $template->cc_content,
            'enclosure_content' => $template->enclosure_content,
            'layout_config' => $template->layout_config ?? [],
            'header_image_url' => $template->headerImageUrl(),
            'footer_image_url' => $template->footerImageUrl(),
            'reference_number_preview' => $this->generateReferenceNumber->preview($template),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function letterPayload(Letter $letter): array
    {
        $letter->loadMissing(['template', 'creator', 'updater']);

        return [
            'id' => $letter->id,
            'template_id' => $letter->template_id,
            'reference_number' => $letter->reference_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'recipient_name' => $letter->recipient_name,
            'recipient_title' => $letter->recipient_title,
            'recipient_organization' => $letter->recipient_organization,
            'recipient_address' => $letter->recipient_address,
            'recipients' => $letter->resolvedRecipients(),
            'subject' => $letter->subject,
            'salutation' => $letter->salutation,
            'body_content' => $letter->body_content,
            'closing_content' => $letter->closing_content,
            'signature_block_content' => $letter->signature_block_content,
            'cc_content' => $letter->cc_content,
            'enclosure_content' => $letter->enclosure_content,
            'header_image_path_snapshot' => $letter->header_image_path_snapshot,
            'footer_image_path_snapshot' => $letter->footer_image_path_snapshot,
            'signature_image_path_snapshot' => $letter->signature_image_path_snapshot,
            'header_image_url' => $letter->headerImageUrl(),
            'footer_image_url' => $letter->footerImageUrl(),
            'signature_image_url' => $letter->signatureImageUrl(),
            'signer_full_name' => $letter->signerFullName(),
            'signer_title' => $letter->signerTitle(),
            'language' => $letter->language,
            'page_size' => $letter->page_size,
            'orientation' => $letter->orientation,
            'status' => $letter->status,
            'approval_status' => $letter->approval_status ?? 'draft',
            'approved_at' => $letter->approved_at?->toIso8601String(),
            'layout_config' => $letter->layout_config ?? [],
            'notes' => $letter->notes,
            'created_at' => $letter->created_at?->toIso8601String(),
            'updated_at' => $letter->updated_at?->toIso8601String(),
            'creator' => $letter->creator ? ['name' => $letter->creator->name] : null,
            'updater' => $letter->updater ? ['name' => $letter->updater->name] : null,
            'approver' => $letter->approver ? ['name' => $letter->approver->name] : null,
            'template' => $letter->template ? [
                'id' => $letter->template->id,
                'name' => $letter->template->name,
                'code' => $letter->template->code,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LetterRequest $request, bool $includeTemplate = true): array
    {
        $keys = [
            'reference_number',
            'reference_number_preview',
            'letter_date',
            'recipients',
            'recipient_name',
            'recipient_title',
            'recipient_organization',
            'recipient_address',
            'subject',
            'salutation',
            'body_content',
            'closing_content',
            'signature_block_content',
            'cc_content',
            'enclosure_content',
            'status',
            'language',
            'page_size',
            'orientation',
            'layout_config',
            'notes',
        ];

        if ($includeTemplate) {
            array_unshift($keys, 'template_id');
        }

        return Arr::only($request->validated(), $keys);
    }
}

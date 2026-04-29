<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateLetterReferenceNumberAction;
use App\Actions\PersistLetterTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LetterTemplateRequest;
use App\Models\LetterTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class LetterTemplateController extends Controller
{
    public function __construct(
        private readonly PersistLetterTemplateAction $persistLetterTemplate,
        private readonly GenerateLetterReferenceNumberAction $generateReferenceNumber,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LetterTemplate::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'in:en,am'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $templates = LetterTemplate::query()
            ->withCount('letters')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('document_type', 'like', "%{$search}%")
                        ->orWhere('reference_prefix', 'like', "%{$search}%");
                });
            })
            ->when($filters['language'] ?? null, fn ($query, string $language) => $query->where('language', $language))
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (LetterTemplate $letterTemplate): array => [
                'id' => $letterTemplate->id,
                'name' => $letterTemplate->name,
                'code' => $letterTemplate->code,
                'document_type' => $letterTemplate->document_type,
                'language' => $letterTemplate->language,
                'page_size' => $letterTemplate->page_size,
                'orientation' => $letterTemplate->orientation,
                'reference_prefix' => $letterTemplate->reference_prefix,
                'is_active' => $letterTemplate->is_active,
                'is_default' => $letterTemplate->is_default,
                'letters_count' => $letterTemplate->letters_count,
                'updated_at' => $letterTemplate->updated_at?->toIso8601String(),
                'can' => [
                    'update' => $request->user()?->can('update', $letterTemplate) ?? false,
                    'delete' => $request->user()?->can('delete', $letterTemplate) ?? false,
                    'preview' => $request->user()?->can('preview', $letterTemplate) ?? false,
                    'print' => $request->user()?->can('print', $letterTemplate) ?? false,
                    'duplicate' => $request->user()?->can('duplicate', $letterTemplate) ?? false,
                ],
            ]);

        return Inertia::render('Admin/LetterTemplates/Index', [
            'filters' => $filters,
            'templates' => $templates,
            'can' => [
                'create' => $request->user()?->can('create', LetterTemplate::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LetterTemplate::class);

        return Inertia::render('Admin/LetterTemplates/Form', [
            'templateItem' => null,
            'canDelete' => false,
            'placeholderFields' => $this->placeholderFields(),
            'previewData' => $this->previewData(),
        ]);
    }

    public function store(LetterTemplateRequest $request): RedirectResponse
    {
        $letterTemplate = $this->persistLetterTemplate->execute(
            $this->payload($request),
            $request->user() ?? abort(403),
        );

        return to_route('letter-templates.edit', $letterTemplate)
            ->with('success', __('letter_templates.flash.created'));
    }

    public function show(LetterTemplate $letterTemplate): Response
    {
        $this->authorize('view', $letterTemplate);

        return Inertia::render('Admin/LetterTemplates/Show', [
            'templateItem' => $this->templatePayload($letterTemplate),
            'previewData' => $this->previewData($letterTemplate),
            'placeholderFields' => $this->placeholderFields(),
            'can' => [
                'update' => request()->user()?->can('update', $letterTemplate) ?? false,
                'delete' => request()->user()?->can('delete', $letterTemplate) ?? false,
                'preview' => request()->user()?->can('preview', $letterTemplate) ?? false,
                'print' => request()->user()?->can('print', $letterTemplate) ?? false,
                'duplicate' => request()->user()?->can('duplicate', $letterTemplate) ?? false,
                'create_letter' => request()->user()?->can('create', \App\Models\Letter::class) ?? false,
            ],
        ]);
    }

    public function edit(LetterTemplate $letterTemplate): Response
    {
        $this->authorize('update', $letterTemplate);

        return Inertia::render('Admin/LetterTemplates/Form', [
            'templateItem' => $this->templatePayload($letterTemplate),
            'canDelete' => request()->user()?->can('delete', $letterTemplate) ?? false,
            'placeholderFields' => $this->placeholderFields(),
            'previewData' => $this->previewData($letterTemplate),
        ]);
    }

    public function update(LetterTemplateRequest $request, LetterTemplate $letterTemplate): RedirectResponse
    {
        $letterTemplate = $this->persistLetterTemplate->execute(
            $this->payload($request),
            $request->user() ?? abort(403),
            $letterTemplate,
        );

        return to_route('letter-templates.edit', $letterTemplate)
            ->with('success', __('letter_templates.flash.updated'));
    }

    public function destroy(LetterTemplate $letterTemplate): RedirectResponse
    {
        $this->authorize('delete', $letterTemplate);

        if ($letterTemplate->letters()->exists()) {
            return to_route('letter-templates.index')
                ->with('error', __('letter_templates.flash.delete_blocked'));
        }

        $letterTemplate->delete();

        return to_route('letter-templates.index')
            ->with('success', __('letter_templates.flash.deleted'));
    }

    public function preview(LetterTemplate $letterTemplate): Response
    {
        $this->authorize('preview', $letterTemplate);

        return Inertia::render('Admin/LetterTemplates/Preview', [
            'templateItem' => $this->templatePayload($letterTemplate),
            'previewData' => $this->previewData($letterTemplate),
        ]);
    }

    public function print(LetterTemplate $letterTemplate): Response
    {
        $this->authorize('print', $letterTemplate);

        return Inertia::render('Admin/LetterTemplates/Print', [
            'templateItem' => $this->templatePayload($letterTemplate),
            'previewData' => $this->previewData($letterTemplate),
        ]);
    }

    public function duplicate(LetterTemplate $letterTemplate): RedirectResponse
    {
        $this->authorize('duplicate', $letterTemplate);

        $copy = $letterTemplate->replicate([
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
        $copy->name = __(':name copy', ['name' => $letterTemplate->name]);
        $copy->code = $this->duplicateCode($letterTemplate->code);
        $copy->is_default = false;
        $copy->current_reference_number = max(0, (int) $copy->reference_start_number - 1);
        $copy->header_image_path = null;
        $copy->footer_image_path = null;
        $copy->created_by = request()->user()?->id;
        $copy->updated_by = request()->user()?->id;
        $copy->save();

        return to_route('letter-templates.edit', $copy)
            ->with('success', __('letter_templates.flash.duplicated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LetterTemplateRequest $request): array
    {
        return Arr::only($request->validated(), [
            'name',
            'code',
            'document_type',
            'language',
            'page_size',
            'orientation',
            'reference_label',
            'reference_prefix',
            'reference_start_number',
            'subject_template',
            'recipient_block_template',
            'salutation_template',
            'body_content',
            'closing_content',
            'signature_block_content',
            'cc_content',
            'enclosure_content',
            'layout_config',
            'numbering_config',
            'is_active',
            'is_default',
            'notes',
            'header_image',
            'footer_image',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(LetterTemplate $letterTemplate): array
    {
        $letterTemplate->loadMissing(['creator', 'updater']);
        $letterTemplate->loadCount('letters');
        $numberingConfig = is_array($letterTemplate->numbering_config) ? $letterTemplate->numbering_config : [];

        return [
            'id' => $letterTemplate->id,
            'name' => $letterTemplate->name,
            'code' => $letterTemplate->code,
            'document_type' => $letterTemplate->document_type,
            'language' => $letterTemplate->language,
            'page_size' => $letterTemplate->page_size,
            'orientation' => $letterTemplate->orientation,
            'reference_label' => $letterTemplate->reference_label,
            'reference_prefix' => $letterTemplate->reference_prefix,
            'reference_start_number' => $letterTemplate->reference_start_number,
            'current_reference_number' => $letterTemplate->current_reference_number,
            'numbering_config' => [
                'separator' => $numberingConfig['separator'] ?? '/',
                'include_year' => (bool) ($numberingConfig['include_year'] ?? true),
                'pad_length' => (int) ($numberingConfig['pad_length'] ?? 4),
            ],
            'subject_template' => $letterTemplate->subject_template,
            'recipient_block_template' => $letterTemplate->recipient_block_template,
            'salutation_template' => $letterTemplate->salutation_template,
            'body_content' => $letterTemplate->body_content,
            'closing_content' => $letterTemplate->closing_content,
            'signature_block_content' => $letterTemplate->signature_block_content,
            'cc_content' => $letterTemplate->cc_content,
            'enclosure_content' => $letterTemplate->enclosure_content,
            'layout_config' => $letterTemplate->layout_config ?? [],
            'header_image_path' => $letterTemplate->header_image_path,
            'footer_image_path' => $letterTemplate->footer_image_path,
            'header_image_url' => $letterTemplate->headerImageUrl(),
            'footer_image_url' => $letterTemplate->footerImageUrl(),
            'next_reference_number_preview' => $this->generateReferenceNumber->preview($letterTemplate),
            'letters_count' => $letterTemplate->letters_count,
            'is_active' => $letterTemplate->is_active,
            'is_default' => $letterTemplate->is_default,
            'notes' => $letterTemplate->notes,
            'created_at' => $letterTemplate->created_at?->toIso8601String(),
            'updated_at' => $letterTemplate->updated_at?->toIso8601String(),
            'creator' => $letterTemplate->creator ? ['name' => $letterTemplate->creator->name] : null,
            'updater' => $letterTemplate->updater ? ['name' => $letterTemplate->updater->name] : null,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function placeholderFields(): array
    {
        return [
            ['token' => '{date}', 'description' => __('letters.placeholders.date')],
            ['token' => '{reference_number}', 'description' => __('letters.placeholders.reference_number')],
            ['token' => '{recipient_name}', 'description' => __('letters.placeholders.recipient_name')],
            ['token' => '{recipient_title}', 'description' => __('letters.placeholders.recipient_title')],
            ['token' => '{recipient_organization}', 'description' => __('letters.placeholders.recipient_organization')],
            ['token' => '{subject}', 'description' => __('letters.placeholders.subject')],
            ['token' => '{sender_name}', 'description' => __('letters.placeholders.sender_name')],
            ['token' => '{sender_title}', 'description' => __('letters.placeholders.sender_title')],
            ['token' => '{department_name}', 'description' => __('letters.placeholders.department_name')],
            ['token' => '{organization_name}', 'description' => __('letters.placeholders.organization_name')],
            ['token' => '{signature_name}', 'description' => __('letters.placeholders.signature_name')],
            ['token' => '{signature_title}', 'description' => __('letters.placeholders.signature_title')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function previewData(?LetterTemplate $letterTemplate = null): array
    {
        return [
            'date' => now()->toDateString(),
            'reference_number' => $letterTemplate ? $this->generateReferenceNumber->preview($letterTemplate) : 'LDMS/2026/0001',
            'recipient_name' => __('letters.sample.recipient_name'),
            'recipient_title' => __('letters.sample.recipient_title'),
            'recipient_organization' => __('letters.sample.recipient_organization'),
            'subject' => __('letters.sample.subject'),
            'sender_name' => __('letters.sample.sender_name'),
            'sender_title' => __('letters.sample.sender_title'),
            'department_name' => __('letters.sample.department_name'),
            'organization_name' => __('letters.sample.organization_name'),
            'signature_name' => __('letters.sample.signature_name'),
            'signature_title' => __('letters.sample.signature_title'),
        ];
    }

    private function duplicateCode(string $baseCode): string
    {
        $index = 1;
        $candidate = "{$baseCode}-COPY";

        while (LetterTemplate::query()->where('code', $candidate)->exists()) {
            $index++;
            $candidate = "{$baseCode}-COPY-{$index}";
        }

        return $candidate;
    }
}

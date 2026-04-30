<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\LetterTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LetterTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letterTemplate = $this->route('letterTemplate');

        return $letterTemplate instanceof LetterTemplate
            ? ($this->user()?->can('update', $letterTemplate) ?? false)
            : ($this->user()?->can('create', LetterTemplate::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $separator = trim((string) $this->input('numbering_separator', '/'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => str($this->input('code'))->upper()->trim()->toString(),
            'document_type' => $this->filled('document_type') ? trim((string) $this->input('document_type')) : null,
            'language' => strtolower(trim((string) $this->input('language', 'en'))),
            'page_size' => strtoupper(trim((string) $this->input('page_size', 'A4'))),
            'orientation' => strtolower(trim((string) $this->input('orientation', 'portrait'))),
            'reference_label' => $this->filled('reference_label') ? trim((string) $this->input('reference_label')) : null,
            'reference_prefix' => $this->filled('reference_prefix') ? trim((string) $this->input('reference_prefix')) : null,
            'reference_start_number' => max(1, (int) $this->input('reference_start_number', 1)),
            'layout_config' => array_filter([
                'margin_top_mm' => $this->nullableInt($this->input('margin_top_mm')),
                'margin_right_mm' => $this->nullableInt($this->input('margin_right_mm')),
                'margin_bottom_mm' => $this->nullableInt($this->input('margin_bottom_mm')),
                'margin_left_mm' => $this->nullableInt($this->input('margin_left_mm')),
                'header_top_margin_mm' => $this->nullableInt($this->input('header_top_margin_mm')),
                'header_bottom_spacing_mm' => $this->nullableInt($this->input('header_bottom_spacing_mm')),
                'footer_top_spacing_mm' => $this->nullableInt($this->input('footer_top_spacing_mm')),
                'footer_bottom_margin_mm' => $this->nullableInt($this->input('footer_bottom_margin_mm')),
                'content_top_margin_mm' => $this->nullableInt($this->input('content_top_margin_mm')),
                'content_bottom_margin_mm' => $this->nullableInt($this->input('content_bottom_margin_mm')),
            ], static fn ($value): bool => $value !== null),
            'numbering_config' => [
                'separator' => $separator === '' ? '/' : $separator,
                'include_year' => filter_var($this->input('numbering_include_year', true), FILTER_VALIDATE_BOOL),
                'pad_length' => max(1, (int) $this->input('numbering_pad_length', 4)),
            ],
        ]);
    }

    public function rules(): array
    {
        /** @var LetterTemplate|null $letterTemplate */
        $letterTemplate = $this->route('letterTemplate');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique(LetterTemplate::class, 'code')
                    ->ignore($letterTemplate?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'document_type' => ['nullable', 'string', 'max:100'],
            'language' => ['required', Rule::in(['en', 'am'])],
            'page_size' => ['required', Rule::in(['A4'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'reference_label' => ['nullable', 'string', 'max:100'],
            'reference_prefix' => ['required', 'string', 'max:50'],
            'reference_start_number' => ['required', 'integer', 'min:1', 'max:99999999'],
            'numbering_separator' => ['nullable', 'string', 'max:5'],
            'numbering_include_year' => ['required', 'boolean'],
            'numbering_pad_length' => ['required', 'integer', 'between:1,8'],
            'header_image' => ['nullable', 'file', 'mimes:png', 'max:4096'],
            'footer_image' => ['nullable', 'file', 'mimes:png', 'max:4096'],
            'subject_template' => ['nullable', 'string'],
            'recipient_block_template' => ['nullable', 'string'],
            'salutation_template' => ['nullable', 'string'],
            'body_content' => ['required', 'string'],
            'closing_content' => ['nullable', 'string'],
            'signature_block_content' => ['nullable', 'string'],
            'cc_content' => ['nullable', 'string'],
            'enclosure_content' => ['nullable', 'string'],
            'margin_top_mm' => ['nullable', 'integer', 'between:5,40'],
            'margin_right_mm' => ['nullable', 'integer', 'between:5,35'],
            'margin_bottom_mm' => ['nullable', 'integer', 'between:5,40'],
            'margin_left_mm' => ['nullable', 'integer', 'between:5,35'],
            'header_top_margin_mm' => ['nullable', 'integer', 'between:0,25'],
            'header_bottom_spacing_mm' => ['nullable', 'integer', 'between:0,25'],
            'footer_top_spacing_mm' => ['nullable', 'integer', 'between:0,25'],
            'footer_bottom_margin_mm' => ['nullable', 'integer', 'between:0,25'],
            'content_top_margin_mm' => ['nullable', 'integer', 'between:0,30'],
            'content_bottom_margin_mm' => ['nullable', 'integer', 'between:0,30'],
            'layout_config' => ['nullable', 'array'],
            'numbering_config' => ['required', 'array'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('letter_templates.fields.name'),
            'code' => __('common.code'),
            'document_type' => __('letter_templates.fields.document_type'),
            'language' => __('common.language'),
            'page_size' => __('letter_templates.fields.page_size'),
            'orientation' => __('letter_templates.fields.orientation'),
            'reference_label' => __('letter_templates.fields.reference_label'),
            'reference_prefix' => __('letter_templates.fields.reference_prefix'),
            'reference_start_number' => __('letter_templates.fields.reference_start_number'),
            'numbering_separator' => __('letter_templates.fields.numbering_separator'),
            'numbering_include_year' => __('letter_templates.fields.numbering_include_year'),
            'numbering_pad_length' => __('letter_templates.fields.numbering_pad_length'),
            'header_image' => __('letter_templates.fields.header_image'),
            'footer_image' => __('letter_templates.fields.footer_image'),
            'subject_template' => __('letter_templates.fields.subject_template'),
            'recipient_block_template' => __('letter_templates.fields.recipient_block_template'),
            'salutation_template' => __('letter_templates.fields.salutation_template'),
            'body_content' => __('letter_templates.fields.body_template'),
            'closing_content' => __('letter_templates.fields.closing_template'),
            'signature_block_content' => __('letter_templates.fields.signature_block_template'),
            'cc_content' => __('letter_templates.fields.cc_template'),
            'enclosure_content' => __('letter_templates.fields.enclosure_template'),
            'margin_top_mm' => __('letter_templates.fields.margin_top'),
            'margin_right_mm' => __('letter_templates.fields.margin_right'),
            'margin_bottom_mm' => __('letter_templates.fields.margin_bottom'),
            'margin_left_mm' => __('letter_templates.fields.margin_left'),
            'header_top_margin_mm' => __('letter_templates.fields.header_top_margin'),
            'header_bottom_spacing_mm' => __('letter_templates.fields.header_bottom_spacing'),
            'footer_top_spacing_mm' => __('letter_templates.fields.footer_top_spacing'),
            'footer_bottom_margin_mm' => __('letter_templates.fields.footer_bottom_margin'),
            'content_top_margin_mm' => __('letter_templates.fields.content_top_margin'),
            'content_bottom_margin_mm' => __('letter_templates.fields.content_bottom_margin'),
            'is_active' => __('common.status'),
            'is_default' => __('letter_templates.default_template'),
            'notes' => __('common.notes'),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}

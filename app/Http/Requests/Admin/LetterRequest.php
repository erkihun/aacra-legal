<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Department;
use App\Models\Letter;
use App\Models\LetterTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class LetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letter = $this->route('letter');

        return $letter instanceof Letter
            ? ($this->user()?->can('update', $letter) ?? false)
            : ($this->user()?->can('create', Letter::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'template_id' => $this->filled('template_id') ? (string) $this->input('template_id') : null,
            'reference_number' => $this->filled('reference_number') ? trim((string) $this->input('reference_number')) : null,
            'reference_number_preview' => $this->filled('reference_number_preview') ? trim((string) $this->input('reference_number_preview')) : null,
            'recipient_name' => $this->filled('recipient_name') ? trim((string) $this->input('recipient_name')) : null,
            'recipient_title' => $this->filled('recipient_title') ? trim((string) $this->input('recipient_title')) : null,
            'recipient_organization' => $this->filled('recipient_organization') ? trim((string) $this->input('recipient_organization')) : null,
            'recipient_address' => $this->filled('recipient_address') ? trim((string) $this->input('recipient_address')) : null,
            'recipients' => $this->normalizeRecipients(),
            'subject' => $this->filled('subject') ? trim((string) $this->input('subject')) : null,
            'salutation' => $this->filled('salutation') ? trim((string) $this->input('salutation')) : null,
            'closing_content' => $this->filled('closing_content') ? trim((string) $this->input('closing_content')) : null,
            'signature_block_content' => $this->filled('signature_block_content') ? trim((string) $this->input('signature_block_content')) : null,
            'cc_content' => $this->filled('cc_content') ? trim((string) $this->input('cc_content')) : null,
            'enclosure_content' => $this->filled('enclosure_content') ? trim((string) $this->input('enclosure_content')) : null,
            'status' => strtolower(trim((string) $this->input('status', 'draft'))),
            'language' => strtolower(trim((string) $this->input('language', 'en'))),
            'page_size' => strtoupper(trim((string) $this->input('page_size', 'A4'))),
            'orientation' => strtolower(trim((string) $this->input('orientation', 'portrait'))),
            'layout_config' => array_filter([
                'margin_top_mm' => $this->nullableInt($this->input('margin_top_mm')),
                'margin_right_mm' => $this->nullableInt($this->input('margin_right_mm')),
                'margin_bottom_mm' => $this->nullableInt($this->input('margin_bottom_mm')),
                'margin_left_mm' => $this->nullableInt($this->input('margin_left_mm')),
                'header_top_margin_mm' => $this->nullableInt($this->input('header_top_margin_mm')),
                'header_bottom_spacing_mm' => $this->nullableInt($this->input('header_bottom_spacing_mm')),
                'footer_top_spacing_mm' => $this->nullableInt($this->input('footer_top_spacing_mm')),
                'footer_left_margin_mm' => $this->nullableInt($this->input('footer_left_margin_mm')),
                'footer_right_margin_mm' => $this->nullableInt($this->input('footer_right_margin_mm')),
                'footer_bottom_margin_mm' => $this->nullableInt($this->input('footer_bottom_margin_mm')),
                'content_top_margin_mm' => $this->nullableInt($this->input('content_top_margin_mm')),
                'content_bottom_margin_mm' => $this->nullableInt($this->input('content_bottom_margin_mm')),
            ], static fn ($value): bool => $value !== null),
        ]);

        if ($this->route('letter') instanceof Letter) {
            return;
        }

        $templateId = $this->input('template_id');

        if (! is_string($templateId) || $templateId === '') {
            return;
        }

        $template = LetterTemplate::query()
            ->whereKey($templateId)
            ->whereNull('deleted_at')
            ->first();

        if (! $template instanceof LetterTemplate) {
            return;
        }

        $defaults = [
            'subject' => $template->subject_template,
            'salutation' => $template->salutation_template,
            'body_content' => $template->body_content,
            'closing_content' => $template->closing_content,
            'signature_block_content' => $template->signature_block_content,
            'cc_content' => $template->cc_content,
            'enclosure_content' => $template->enclosure_content,
            'language' => $template->language,
            'page_size' => $template->page_size,
            'orientation' => $template->orientation,
            'layout_config' => $template->layout_config ?? [],
        ];

        $merged = [];

        foreach ($defaults as $key => $value) {
            $currentValue = $this->input($key);

            if (in_array($key, ['language', 'page_size', 'orientation'], true)) {
                $merged[$key] = filled($currentValue) ? $currentValue : $value;

                continue;
            }

            if ($key === 'layout_config') {
                $defaultLayout = is_array($value) ? $value : [];
                $inputLayout = $this->input('layout_config');

                $merged[$key] = [
                    ...$defaultLayout,
                    ...(is_array($inputLayout) ? $inputLayout : []),
                ];

                continue;
            }

            $merged[$key] = filled($currentValue) ? $currentValue : $value;
        }

        if (is_array($merged['layout_config'] ?? null)) {
            $layoutConfig = $merged['layout_config'];
            $merged = [
                ...$merged,
                'margin_top_mm' => Arr::get($layoutConfig, 'margin_top_mm', $this->input('margin_top_mm')),
                'margin_right_mm' => Arr::get($layoutConfig, 'margin_right_mm', $this->input('margin_right_mm')),
                'margin_bottom_mm' => Arr::get($layoutConfig, 'margin_bottom_mm', $this->input('margin_bottom_mm')),
                'margin_left_mm' => Arr::get($layoutConfig, 'margin_left_mm', $this->input('margin_left_mm')),
                'header_top_margin_mm' => Arr::get($layoutConfig, 'header_top_margin_mm', $this->input('header_top_margin_mm')),
                'header_bottom_spacing_mm' => Arr::get($layoutConfig, 'header_bottom_spacing_mm', $this->input('header_bottom_spacing_mm')),
                'footer_top_spacing_mm' => Arr::get($layoutConfig, 'footer_top_spacing_mm', $this->input('footer_top_spacing_mm')),
                'footer_left_margin_mm' => Arr::get($layoutConfig, 'footer_left_margin_mm', $this->input('footer_left_margin_mm')),
                'footer_right_margin_mm' => Arr::get($layoutConfig, 'footer_right_margin_mm', $this->input('footer_right_margin_mm')),
                'footer_bottom_margin_mm' => Arr::get($layoutConfig, 'footer_bottom_margin_mm', $this->input('footer_bottom_margin_mm')),
                'content_top_margin_mm' => Arr::get($layoutConfig, 'content_top_margin_mm', $this->input('content_top_margin_mm')),
                'content_bottom_margin_mm' => Arr::get($layoutConfig, 'content_bottom_margin_mm', $this->input('content_bottom_margin_mm')),
            ];
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Letter|null $letter */
        $letter = $this->route('letter');
        $templateIdRule = Rule::exists(LetterTemplate::class, 'id')->where(function ($query) use ($letter) {
            $query->whereNull('deleted_at');

            if ($letter instanceof Letter) {
                $query->where(function ($builder) use ($letter): void {
                    $builder->where('is_active', true)->orWhere('id', $letter->template_id);
                });

                return;
            }

            $query->where('is_active', true);
        });

        return [
            'template_id' => [
                $letter instanceof Letter ? 'nullable' : 'required',
                'uuid',
                $templateIdRule,
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique(Letter::class, 'reference_number')
                    ->ignore($letter?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'reference_number_preview' => ['nullable', 'string', 'max:120'],
            'letter_date' => ['required', 'date'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_title' => ['nullable', 'string', 'max:255'],
            'recipient_organization' => ['nullable', 'string', 'max:255'],
            'recipient_address' => ['nullable', 'string'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $name = is_array($value) ? trim((string) Arr::get($value, 'recipient_name', '')) : '';
                    $departmentId = is_array($value) ? trim((string) Arr::get($value, 'recipient_department_id', '')) : '';

                    if ($name === '' && $departmentId === '') {
                        $fail(__('letters.validation.recipient_row_required'));
                    }
                },
            ],
            'recipients.*.recipient_name' => ['nullable', 'string', 'max:255'],
            'recipients.*.recipient_department_id' => [
                'nullable',
                'uuid',
                Rule::exists(Department::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'subject' => ['nullable', 'string', 'max:255'],
            'salutation' => ['nullable', 'string'],
            'body_content' => ['required', 'string'],
            'closing_content' => ['nullable', 'string'],
            'signature_block_content' => ['nullable', 'string'],
            'cc_content' => ['nullable', 'string'],
            'enclosure_content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'archived', 'final'])],
            'language' => ['required', Rule::in(['en', 'am'])],
            'page_size' => ['required', Rule::in(['A4'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'margin_top_mm' => ['nullable', 'integer', 'between:1,40'],
            'margin_right_mm' => ['nullable', 'integer', 'between:1,35'],
            'margin_bottom_mm' => ['nullable', 'integer', 'between:1,40'],
            'margin_left_mm' => ['nullable', 'integer', 'between:1,35'],
            'header_top_margin_mm' => ['nullable', 'integer', 'between:1,25'],
            'header_bottom_spacing_mm' => ['nullable', 'integer', 'between:1,25'],
            'footer_top_spacing_mm' => ['nullable', 'integer', 'between:1,25'],
            'footer_left_margin_mm' => ['nullable', 'integer', 'between:1,35'],
            'footer_right_margin_mm' => ['nullable', 'integer', 'between:1,35'],
            'footer_bottom_margin_mm' => ['nullable', 'integer', 'between:1,25'],
            'content_top_margin_mm' => ['nullable', 'integer', 'between:1,30'],
            'content_bottom_margin_mm' => ['nullable', 'integer', 'between:1,30'],
            'layout_config' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'template_id' => __('letters.fields.template'),
            'reference_number' => __('letters.fields.reference_number'),
            'letter_date' => __('letters.fields.letter_date'),
            'recipient_name' => __('letters.fields.recipient_name'),
            'recipient_title' => __('letters.fields.recipient_title'),
            'recipient_organization' => __('letters.fields.recipient_organization'),
            'recipient_address' => __('letters.fields.recipient_address'),
            'recipients' => __('letters.fields.recipients'),
            'recipients.*.recipient_name' => __('letters.fields.recipient_name'),
            'recipients.*.recipient_department_id' => __('letters.fields.recipient_department'),
            'subject' => __('letters.fields.subject'),
            'salutation' => __('letters.fields.salutation'),
            'body_content' => __('letters.fields.body_content'),
            'closing_content' => __('letters.fields.closing_content'),
            'signature_block_content' => __('letters.fields.signature_block_content'),
            'cc_content' => __('letters.fields.cc_content'),
            'enclosure_content' => __('letters.fields.enclosure_content'),
            'status' => __('common.status'),
            'language' => __('common.language'),
            'page_size' => __('letters.fields.page_size'),
            'orientation' => __('letters.fields.orientation'),
            'margin_top_mm' => __('letters.fields.margin_top'),
            'margin_right_mm' => __('letters.fields.margin_right'),
            'margin_bottom_mm' => __('letters.fields.margin_bottom'),
            'margin_left_mm' => __('letters.fields.margin_left'),
            'header_top_margin_mm' => __('letter_templates.fields.header_top_margin'),
            'header_bottom_spacing_mm' => __('letter_templates.fields.header_bottom_spacing'),
            'footer_top_spacing_mm' => __('letter_templates.fields.footer_top_spacing'),
            'footer_left_margin_mm' => __('letter_templates.fields.footer_left_margin'),
            'footer_right_margin_mm' => __('letter_templates.fields.footer_right_margin'),
            'footer_bottom_margin_mm' => __('letter_templates.fields.footer_bottom_margin'),
            'content_top_margin_mm' => __('letter_templates.fields.content_top_margin'),
            'content_bottom_margin_mm' => __('letter_templates.fields.content_bottom_margin'),
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

    /**
     * @return array<int, array<string, string|null>>
     */
    private function normalizeRecipients(): array
    {
        $recipients = $this->input('recipients');

        if (is_array($recipients)) {
            return collect($recipients)
                ->map(function (mixed $recipient): ?array {
                    if (! is_array($recipient)) {
                        return null;
                    }

                    $name = trim((string) Arr::get($recipient, 'recipient_name', ''));
                    $departmentId = trim((string) Arr::get($recipient, 'recipient_department_id', ''));

                    return [
                        'recipient_name' => $name !== '' ? $name : null,
                        'recipient_department_id' => $departmentId !== '' ? $departmentId : null,
                    ];
                })
                ->values()
                ->all();
        }

        $recipientName = trim((string) $this->input('recipient_name', ''));
        $legacyDepartmentId = trim((string) $this->input('recipient_department_id', ''));

        if ($recipientName === '' && $legacyDepartmentId === '') {
            return [];
        }

        return [[
            'recipient_name' => $recipientName !== '' ? $recipientName : null,
            'recipient_department_id' => $legacyDepartmentId !== '' ? $legacyDepartmentId : null,
        ]];
    }
}

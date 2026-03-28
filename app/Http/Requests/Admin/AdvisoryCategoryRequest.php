<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AdvisoryCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvisoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advisoryCategory = $this->route('advisoryCategory');

        return $advisoryCategory instanceof AdvisoryCategory
            ? ($this->user()?->can('update', $advisoryCategory) ?? false)
            : ($this->user()?->can('create', AdvisoryCategory::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => str($this->input('code'))->upper()->trim()->toString(),
            'name_en' => trim((string) $this->input('name_en')),
            'name_am' => trim((string) $this->input('name_am')),
        ]);
    }

    public function rules(): array
    {
        /** @var AdvisoryCategory|null $advisoryCategory */
        $advisoryCategory = $this->route('advisoryCategory');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(AdvisoryCategory::class, 'code')
                    ->ignore($advisoryCategory?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

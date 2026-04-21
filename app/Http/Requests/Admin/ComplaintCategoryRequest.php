<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ComplaintCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $complaintCategory = $this->route('complaintCategory');

        return $complaintCategory instanceof ComplaintCategory
            ? ($this->user()?->can('update', $complaintCategory) ?? false)
            : ($this->user()?->can('create', ComplaintCategory::class) ?? false);
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
        /** @var ComplaintCategory|null $complaintCategory */
        $complaintCategory = $this->route('complaintCategory');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(ComplaintCategory::class, 'code')
                    ->ignore($complaintCategory?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

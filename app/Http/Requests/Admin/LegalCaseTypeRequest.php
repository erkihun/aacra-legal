<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\CaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegalCaseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $caseType = $this->route('caseType');

        return $caseType instanceof CaseType
            ? ($this->user()?->can('update', $caseType) ?? false)
            : ($this->user()?->can('create', CaseType::class) ?? false);
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
        /** @var CaseType|null $caseType */
        $caseType = $this->route('caseType');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(CaseType::class, 'code')
                    ->ignore($caseType?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

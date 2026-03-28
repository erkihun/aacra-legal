<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof Department
            ? ($this->user()?->can('update', $department) ?? false)
            : ($this->user()?->can('create', Department::class) ?? false);
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
        /** @var Department|null $department */
        $department = $this->route('department');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Department::class, 'code')
                    ->ignore($department?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

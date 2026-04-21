<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branch = $this->route('branch');

        return $branch instanceof Branch
            ? ($this->user()?->can('update', $branch) ?? false)
            : ($this->user()?->can('create', Branch::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => str($this->input('code'))->upper()->trim()->toString(),
            'name_en' => trim((string) $this->input('name_en')),
            'name_am' => trim((string) $this->input('name_am')),
            'region' => $this->stringOrNull('region'),
            'city' => $this->stringOrNull('city'),
            'address' => $this->stringOrNull('address'),
            'phone' => $this->stringOrNull('phone'),
            'email' => $this->stringOrNull('email'),
            'manager_name' => $this->stringOrNull('manager_name'),
            'notes' => $this->stringOrNull('notes'),
            'is_active' => $this->boolean('is_active'),
            'is_head_office' => $this->boolean('is_head_office'),
        ]);
    }

    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Branch::class, 'code')
                    ->ignore($branch?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'is_head_office' => ['required', 'boolean'],
        ];
    }

    private function stringOrNull(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value !== '' ? $value : null;
    }
}

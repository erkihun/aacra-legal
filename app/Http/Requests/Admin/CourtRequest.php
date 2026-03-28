<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Court;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        $court = $this->route('court');

        return $court instanceof Court
            ? ($this->user()?->can('update', $court) ?? false)
            : ($this->user()?->can('create', Court::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => str($this->input('code'))->upper()->trim()->toString(),
            'name_en' => trim((string) $this->input('name_en')),
            'name_am' => trim((string) $this->input('name_am')),
            'level' => $this->filled('level') ? trim((string) $this->input('level')) : null,
            'city' => $this->filled('city') ? trim((string) $this->input('city')) : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Court|null $court */
        $court = $this->route('court');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Court::class, 'code')
                    ->ignore($court?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

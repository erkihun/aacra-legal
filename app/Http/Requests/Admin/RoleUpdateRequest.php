<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SystemRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') || $this->user()?->can('users.assign_roles') || false;
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');
        $reservedNames = $role?->name === SystemRole::SUPER_ADMIN->value ? [] : [SystemRole::SUPER_ADMIN->value];

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($role?->getKey())
                    ->where('guard_name', 'web'),
                Rule::notIn($reservedNames),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}

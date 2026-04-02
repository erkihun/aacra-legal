<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SystemRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn([SystemRole::SUPER_ADMIN->value]),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}

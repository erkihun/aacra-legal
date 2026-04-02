<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RolePermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') || $this->user()?->can('users.assign_roles') || false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}

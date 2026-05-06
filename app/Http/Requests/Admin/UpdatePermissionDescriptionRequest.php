<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.manage') || $this->user()?->can('users.assign_roles') || false;
    }

    public function rules(): array
    {
        return [
            'description_en' => ['required', 'string', 'min:10', 'max:1000'],
            'description_am' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }
}

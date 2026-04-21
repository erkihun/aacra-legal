<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterComplaintClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Requester;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:requester_accounts,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

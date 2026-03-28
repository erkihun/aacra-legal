<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\Department;
use App\Models\User;
use App\Services\SystemSettingsService;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequesterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'job_title' => $this->filled('job_title') ? trim((string) $this->input('job_title')) : null,
        ]);
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => [
                'required',
                'uuid',
                Rule::exists(Department::class, 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('code', '!=', 'LEG')),
            ],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', app(PasswordRules::class)->rule()],
            'locale' => ['nullable', Rule::in($settings->supportedLocales())],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('auth.name'),
            'email' => __('auth.email'),
            'phone' => __('profile.phone'),
            'department_id' => __('auth.department'),
            'job_title' => __('auth.position_title'),
            'password' => __('auth.password'),
        ];
    }
}

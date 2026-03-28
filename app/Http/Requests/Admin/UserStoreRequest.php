<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Services\SystemSettingsService;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => str($this->input('email'))->lower()->trim()->toString(),
            'employee_number' => $this->filled('employee_number') ? str($this->input('employee_number'))->upper()->trim()->toString() : null,
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'job_title' => $this->filled('job_title') ? trim((string) $this->input('job_title')) : null,
        ]);
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);

        return [
            'department_id' => ['nullable', Rule::exists(Department::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'team_id' => ['nullable', Rule::exists(Team::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class, 'employee_number')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique(User::class, 'email')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in($settings->supportedLocales())],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'confirmed', app(PasswordRules::class)->rule()],
            'role_name' => array_filter([
                $this->user()?->can('roles.manage') || $this->user()?->can('users.assign_roles') ? 'required' : 'nullable',
                'string',
                Rule::exists('roles', 'name'),
            ]),
        ];
    }
}

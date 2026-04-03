<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => str($this->input('email'))->lower()->trim()->toString(),
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'national_id' => $this->filled('national_id')
                ? preg_replace('/\s+/', '', trim((string) $this->input('national_id')))
                : null,
            'telegram_username' => $this->filled('telegram_username')
                ? trim((string) $this->input('telegram_username'))
                : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['required', Rule::in($settings->supportedLocales())],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'stamp' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'national_id' => ['nullable', 'regex:/^\d{16}$/'],
            'telegram_username' => ['nullable', 'regex:/^@[A-Za-z0-9_]{5,32}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'avatar' => __('users.avatar'),
            'signature' => __('users.signature'),
            'stamp' => __('users.stamp'),
            'national_id' => __('users.national_id'),
            'telegram_username' => __('users.telegram_username'),
        ];
    }
}

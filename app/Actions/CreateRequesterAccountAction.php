<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateRequesterAccountAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $locale = (string) ($attributes['locale'] ?? $this->settings->defaultLocale());

            $user = User::query()->create([
                'department_id' => $attributes['department_id'],
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'job_title' => $attributes['job_title'] ?? null,
                'locale' => LocaleCode::from($locale),
                'email_verified_at' => now(),
                'is_active' => true,
                'password' => Hash::make((string) $attributes['password']),
            ]);

            $user->syncRoles([SystemRole::DEPARTMENT_REQUESTER->value]);

            return $user;
        });
    }
}

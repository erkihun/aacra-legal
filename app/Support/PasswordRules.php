<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SystemSettingGroup;
use App\Services\SystemSettingsService;
use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function rule(): Password
    {
        $security = $this->settings->group(SystemSettingGroup::SECURITY);
        $minimumLength = max(8, (int) ($security['password_min_length'] ?? 8));
        $complexityEnabled = (bool) ($security['password_complexity_enabled'] ?? false);

        $rule = Password::min($minimumLength);

        if ($complexityEnabled) {
            $rule = $rule
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        }

        return $rule;
    }
}

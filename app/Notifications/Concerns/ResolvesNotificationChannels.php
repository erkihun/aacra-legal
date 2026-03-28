<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Services\SystemSettingsService;

trait ResolvesNotificationChannels
{
    /**
     * @return array<int, string>
     */
    protected function resolveChannels(): array
    {
        $settings = app(SystemSettingsService::class);
        $channels = [];

        if ($settings->notificationsEnabled('database')) {
            $channels[] = 'database';
        }

        if ($settings->notificationsEnabled('mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}

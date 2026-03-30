<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\SystemSettingsService;
use App\Services\Telegram\TelegramGateway;
use Illuminate\Validation\ValidationException;

class SendTelegramTestMessageAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
        private readonly TelegramGateway $gateway,
    ) {}

    public function execute(User $actor): void
    {
        $telegram = $this->settings->telegramConfiguration();

        if (! $telegram['enabled']) {
            throw ValidationException::withMessages([
                'telegram' => 'Telegram is currently disabled. Enable the Telegram provider and save the settings before sending a test message.',
            ]);
        }

        if (! $telegram['has_bot_token']) {
            throw ValidationException::withMessages([
                'telegram' => 'A Telegram bot token must be saved before a test message can be sent.',
            ]);
        }

        if (! is_string($telegram['default_chat_target']) || trim($telegram['default_chat_target']) === '') {
            throw ValidationException::withMessages([
                'telegram' => 'A default Telegram chat ID is required before a test message can be sent.',
            ]);
        }

        $result = $this->gateway->send(
            $telegram['default_chat_target'],
            sprintf(
                "Telegram test message from %s\nSent by %s at %s",
                config('app.name'),
                $actor->name,
                now()->toDateTimeString(),
            ),
        );

        if (! $result->sent) {
            throw ValidationException::withMessages([
                'telegram' => $result->error ?? 'Telegram test message could not be sent.',
            ]);
        }
    }
}

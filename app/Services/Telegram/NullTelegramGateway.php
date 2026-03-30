<?php

declare(strict_types=1);

namespace App\Services\Telegram;

class NullTelegramGateway implements TelegramGateway
{
    public function send(string $chatId, string $message): TelegramSendResult
    {
        return TelegramSendResult::failed('Telegram delivery is disabled by the configured driver.');
    }
}

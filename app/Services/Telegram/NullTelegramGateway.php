<?php

declare(strict_types=1);

namespace App\Services\Telegram;

class NullTelegramGateway implements TelegramGateway
{
    public function send(string $chatId, string $message): void {}
}

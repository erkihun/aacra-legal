<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;

class LogTelegramGateway implements TelegramGateway
{
    public function send(string $chatId, string $message): TelegramSendResult
    {
        Log::channel(config('logging.default'))->info('Telegram notification', [
            'chat_id' => $chatId,
            'message' => $message,
        ]);

        return TelegramSendResult::failed('Telegram is using the log driver. Save a valid bot token to send real messages.');
    }
}

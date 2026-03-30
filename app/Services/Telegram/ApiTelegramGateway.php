<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiTelegramGateway implements TelegramGateway
{
    public function send(string $chatId, string $message): TelegramSendResult
    {
        $botToken = trim((string) config('services.telegram.bot_token'));
        $normalizedChatId = trim($chatId);

        if ($botToken === '') {
            Log::warning('Telegram delivery skipped because the bot token is not configured.');

            return TelegramSendResult::failed('A Telegram bot token is required before messages can be sent.');
        }

        if ($normalizedChatId === '') {
            Log::warning('Telegram delivery skipped because the target chat is not configured.');

            return TelegramSendResult::failed('A Telegram chat ID is required before messages can be sent.');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $normalizedChatId,
                    'text' => $message,
                    'disable_web_page_preview' => true,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Telegram API connection failed.', [
                'chat_id' => $normalizedChatId,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return TelegramSendResult::failed(
                'Telegram could not be reached. Check network connectivity and try again.',
                retryable: true,
            );
        }

        if ($response->successful() && $response->json('ok') === true) {
            return TelegramSendResult::sent();
        }

        $description = Str::limit(trim((string) ($response->json('description') ?? $response->body())), 200);

        Log::warning('Telegram API rejected a message.', [
            'chat_id' => $normalizedChatId,
            'status' => $response->status(),
            'description' => $description,
        ]);

        return TelegramSendResult::failed(
            $this->friendlyError($response->status(), $description),
            retryable: $response->serverError() || $response->status() === 429,
        );
    }

    private function friendlyError(int $status, string $description): string
    {
        return match ($status) {
            400 => $description !== '' ? $description : 'Telegram rejected the chat target or message payload.',
            401 => 'Telegram rejected the bot token. Generate a fresh token in BotFather and save it again.',
            403 => 'Telegram denied access to the target chat. Confirm the bot has started the chat or been added to it.',
            404 => 'Telegram could not find the bot endpoint. Verify the saved bot token.',
            429 => 'Telegram rate limited the request. Please wait and try again.',
            default => 'Telegram could not send the message at this time.',
        };
    }
}

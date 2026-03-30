<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Telegram\TelegramGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $chatId,
        public readonly string $message,
    ) {}

    public function handle(TelegramGateway $gateway): void
    {
        $result = $gateway->send($this->chatId, $this->message);

        if (! $result->sent && $result->retryable) {
            throw new \RuntimeException($result->error ?? 'Telegram delivery failed.');
        }

        if (! $result->sent) {
            Log::warning('Telegram delivery skipped.', [
                'chat_id' => $this->chatId,
                'reason' => $result->error,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Telegram delivery job failed.', [
            'chat_id' => $this->chatId,
            'message_preview' => Str::limit($this->message, 120),
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);
    }
}

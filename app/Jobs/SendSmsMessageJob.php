<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $recipient,
        public readonly string $message,
    ) {}

    public function handle(SmsGateway $gateway): void
    {
        $gateway->send($this->recipient, $this->message);
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
        Log::error('SMS delivery job failed.', [
            'recipient' => $this->recipient,
            'message' => $this->message,
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
        ]);
    }
}

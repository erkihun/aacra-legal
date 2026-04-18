<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\NotificationDeliveryDeduplicator;
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
        public readonly ?string $dedupeKey = null,
    ) {}

    public function handle(SmsGateway $gateway, NotificationDeliveryDeduplicator $deduplicator): void
    {
        $fingerprint = $this->fingerprint();

        if ($deduplicator->has($fingerprint)) {
            Log::info('SMS delivery skipped as a duplicate.', [
                'recipient' => $this->recipient,
            ]);

            return;
        }

        $gateway->send($this->recipient, $this->message);
        $deduplicator->remember($fingerprint);
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

    private function fingerprint(): string
    {
        return 'sms|'.sha1(($this->dedupeKey ?? "{$this->recipient}|{$this->message}"));
    }
}

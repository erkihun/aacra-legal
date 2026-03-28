<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function send(string $recipient, string $message): void
    {
        Log::channel(config('logging.default'))->info('SMS notification', [
            'recipient' => $recipient,
            'message' => $message,
        ]);
    }
}

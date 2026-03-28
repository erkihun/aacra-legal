<?php

declare(strict_types=1);

namespace App\Services\Sms;

class NullSmsGateway implements SmsGateway
{
    public function send(string $recipient, string $message): void {}
}

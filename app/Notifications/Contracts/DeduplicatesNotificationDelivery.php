<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

interface DeduplicatesNotificationDelivery
{
    public function dedupeFingerprint(object $notifiable, string $channel): string;
}

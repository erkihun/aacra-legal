<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

trait BuildsNotificationDedupeKey
{
    /**
     * @param  array<string, scalar|null>  $context
     */
    protected function buildDedupeFingerprint(
        object $notifiable,
        string $channel,
        string $eventType,
        array $context = [],
    ): string {
        $recipientKey = method_exists($notifiable, 'getKey')
            ? (string) ($notifiable->getKey() ?? spl_object_id($notifiable))
            : spl_object_hash($notifiable);

        ksort($context);

        $segments = [
            'notification',
            static::class,
            $eventType,
            $channel,
            $recipientKey,
        ];

        foreach ($context as $key => $value) {
            $segments[] = "{$key}:".($value === null ? 'null' : (string) $value);
        }

        return implode('|', $segments);
    }
}

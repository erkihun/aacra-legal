<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use App\Services\NotificationDeliveryDeduplicator;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;

class NotificationDeliverySubscriber
{
    public function __construct(
        private readonly NotificationDeliveryDeduplicator $deduplicator,
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(NotificationSending::class, [$this, 'whenSending']);
        $events->listen(NotificationSent::class, [$this, 'whenSent']);
    }

    public function whenSending(NotificationSending $event): ?bool
    {
        if (! $event->notification instanceof DeduplicatesNotificationDelivery) {
            return null;
        }

        $fingerprint = $event->notification->dedupeFingerprint($event->notifiable, $event->channel);

        return $this->deduplicator->has($fingerprint) ? false : null;
    }

    public function whenSent(NotificationSent $event): void
    {
        if (! $event->notification instanceof DeduplicatesNotificationDelivery) {
            return;
        }

        $fingerprint = $event->notification->dedupeFingerprint($event->notifiable, $event->channel);

        $this->deduplicator->remember($fingerprint);
    }
}

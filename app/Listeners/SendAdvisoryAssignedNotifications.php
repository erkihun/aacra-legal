<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdvisoryAssigned;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Notifications\AdvisoryAssignedNotification;
use App\Services\SystemSettingsService;

class SendAdvisoryAssignedNotifications
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function handle(AdvisoryAssigned $event): void
    {
        $event->assignee->notify(new AdvisoryAssignedNotification($event->advisoryRequest, $event->assignedBy));

        if ($this->settings->notificationsEnabled('sms') && $event->assignee->phone !== null) {
            $dedupeKey = implode('|', [
                'advisory.assigned',
                (string) $event->advisoryRequest->getKey(),
                (string) $event->assignee->getKey(),
                'sms',
            ]);

            SendSmsMessageJob::dispatch(
                $event->assignee->phone,
                "Advisory {$event->advisoryRequest->request_number} assigned to you.",
                $dedupeKey,
            );
        }

        if ($this->settings->notificationsEnabled('telegram') && filled($event->assignee->telegram_chat_id ?? null)) {
            $dedupeKey = implode('|', [
                'advisory.assigned',
                (string) $event->advisoryRequest->getKey(),
                (string) $event->assignee->getKey(),
                'telegram',
            ]);

            SendTelegramMessageJob::dispatch(
                $event->assignee->telegram_chat_id,
                "Advisory {$event->advisoryRequest->request_number} assigned to you.",
                $dedupeKey,
            );
        }
    }
}

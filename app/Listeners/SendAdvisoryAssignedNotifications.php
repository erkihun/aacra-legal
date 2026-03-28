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
            SendSmsMessageJob::dispatch(
                $event->assignee->phone,
                "Advisory {$event->advisoryRequest->request_number} assigned to you.",
            );
        }

        if ($this->settings->notificationsEnabled('telegram') && filled($event->assignee->telegram_chat_id ?? null)) {
            SendTelegramMessageJob::dispatch(
                $event->assignee->telegram_chat_id,
                "Advisory {$event->advisoryRequest->request_number} assigned to you.",
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CaseAssigned;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Notifications\CaseAssignedNotification;
use App\Services\SystemSettingsService;

class SendCaseAssignedNotifications
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function handle(CaseAssigned $event): void
    {
        $event->assignee->notify(new CaseAssignedNotification($event->legalCase, $event->assignedBy));

        if ($this->settings->notificationsEnabled('sms') && $event->assignee->phone !== null) {
            $dedupeKey = implode('|', [
                'case.assigned',
                (string) $event->legalCase->getKey(),
                (string) $event->assignee->getKey(),
                'sms',
            ]);

            SendSmsMessageJob::dispatch(
                $event->assignee->phone,
                "Case {$event->legalCase->case_number} assigned to you.",
                $dedupeKey,
            );
        }

        if ($this->settings->notificationsEnabled('telegram') && filled($event->assignee->telegram_chat_id ?? null)) {
            $dedupeKey = implode('|', [
                'case.assigned',
                (string) $event->legalCase->getKey(),
                (string) $event->assignee->getKey(),
                'telegram',
            ]);

            SendTelegramMessageJob::dispatch(
                $event->assignee->telegram_chat_id,
                "Case {$event->legalCase->case_number} assigned to you.",
                $dedupeKey,
            );
        }
    }
}

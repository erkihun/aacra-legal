<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseAssignedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly LegalCase $legalCase,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'case.assigned', [
            'legal_case_id' => $this->legalCase->getKey(),
            'assigned_by_id' => $this->assignedBy->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case.assigned',
            'title' => 'Legal case assigned',
            'case_number' => $this->legalCase->case_number,
            'assigned_by' => $this->assignedBy->name,
            'url' => route('cases.show', $this->legalCase),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Case assignment: {$this->legalCase->case_number}")
            ->line("You have been assigned legal case {$this->legalCase->case_number}.")
            ->action('Open case', route('cases.show', $this->legalCase));
    }
}

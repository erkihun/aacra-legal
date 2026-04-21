<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use App\Models\ComplaintCommitteeDecision;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintCommitteeDecisionIssuedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintCommitteeDecision $decision,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'complaint.committee_decision', [
            'complaint_id' => $this->complaint->getKey(),
            'decision_id' => $this->decision->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint.committee_decision',
            'title' => 'Complaint committee decision issued',
            'complaint_id' => $this->complaint->getKey(),
            'complaint_number' => $this->complaint->complaint_number,
            'decision_id' => $this->decision->getKey(),
            'subject' => $this->complaint->subject,
            'outcome' => $this->decision->outcome?->value,
            'url' => route('complaints.show', $this->complaint),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Committee decision issued: {$this->complaint->complaint_number}")
            ->line("The complaint committee has issued a final decision for complaint {$this->complaint->complaint_number}.")
            ->line("Subject: {$this->complaint->subject}")
            ->line("Outcome: ".($this->decision->outcome?->value ?? ''))
            ->action('Open complaint', route('complaints.show', $this->complaint));
    }
}

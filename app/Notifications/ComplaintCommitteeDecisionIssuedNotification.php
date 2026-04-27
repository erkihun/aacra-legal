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
            'title' => __('complaints.notifications.committee_decision.title'),
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
            ->subject(__('complaints.notifications.committee_decision.mail.subject', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.committee_decision.mail.line_1', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.common.subject_line', [
                'subject' => $this->complaint->subject,
            ]))
            ->line(__('complaints.notifications.committee_decision.mail.outcome_line', [
                'outcome' => $this->decision->outcome !== null
                    ? __("complaints.committee_outcomes.{$this->decision->outcome->value}")
                    : '-',
            ]))
            ->action(__('complaints.notifications.common.open'), route('complaints.show', $this->complaint));
    }
}

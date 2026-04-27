<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ComplaintEscalationType;
use App\Models\Complaint;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintEscalatedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintEscalationType $escalationType,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'complaint.escalated', [
            'complaint_id' => $this->complaint->getKey(),
            'escalation_type' => $this->escalationType->value,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint.escalated',
            'title' => __('complaints.notifications.escalated.title'),
            'complaint_id' => $this->complaint->getKey(),
            'complaint_number' => $this->complaint->complaint_number,
            'subject' => $this->complaint->subject,
            'escalation_type' => $this->escalationType->value,
            'url' => route('complaints.show', $this->complaint),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('complaints.notifications.escalated.mail.subject', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.escalated.mail.line_1', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.common.subject_line', [
                'subject' => $this->complaint->subject,
            ]))
            ->action(__('complaints.notifications.common.open'), route('complaints.show', $this->complaint));
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintAssignedToDepartmentNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly Complaint $complaint,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'complaint.assigned_department', [
            'complaint_id' => $this->complaint->getKey(),
            'department_id' => $this->complaint->department_id,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint.assigned_department',
            'title' => __('complaints.notifications.assigned_department.title'),
            'complaint_id' => $this->complaint->getKey(),
            'complaint_number' => $this->complaint->complaint_number,
            'subject' => $this->complaint->subject,
            'url' => route('complaints.show', $this->complaint),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('complaints.notifications.assigned_department.mail.subject', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.assigned_department.mail.line_1', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.common.subject_line', [
                'subject' => $this->complaint->subject,
            ]))
            ->action(__('complaints.notifications.common.open'), route('complaints.show', $this->complaint));
    }
}

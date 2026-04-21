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
            'title' => 'Complaint routed to your department',
            'complaint_id' => $this->complaint->getKey(),
            'complaint_number' => $this->complaint->complaint_number,
            'subject' => $this->complaint->subject,
            'url' => route('complaints.show', $this->complaint),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Complaint assigned: {$this->complaint->complaint_number}")
            ->line("Complaint {$this->complaint->complaint_number} has been routed to your department.")
            ->line("Subject: {$this->complaint->subject}")
            ->action('Open complaint', route('complaints.show', $this->complaint));
    }
}

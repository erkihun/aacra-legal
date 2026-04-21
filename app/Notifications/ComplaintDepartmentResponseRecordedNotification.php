<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Complaint;
use App\Models\ComplaintResponse;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintDepartmentResponseRecordedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintResponse $response,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'complaint.department_response', [
            'complaint_id' => $this->complaint->getKey(),
            'response_id' => $this->response->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'complaint.department_response',
            'title' => 'Department response recorded',
            'complaint_id' => $this->complaint->getKey(),
            'complaint_number' => $this->complaint->complaint_number,
            'response_id' => $this->response->getKey(),
            'subject' => $this->complaint->subject,
            'url' => route('complaints.show', $this->complaint),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Department response received: {$this->complaint->complaint_number}")
            ->line("Your complaint {$this->complaint->complaint_number} has received a department response.")
            ->line("Subject: {$this->complaint->subject}")
            ->action('Open complaint', route('complaints.show', $this->complaint));
    }
}

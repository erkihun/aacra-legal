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
            'title' => __('complaints.notifications.department_response.title'),
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
            ->subject(__('complaints.notifications.department_response.mail.subject', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.department_response.mail.line_1', [
                'complaint_number' => $this->complaint->complaint_number,
            ]))
            ->line(__('complaints.notifications.common.subject_line', [
                'subject' => $this->complaint->subject,
            ]))
            ->action(__('complaints.notifications.common.open'), route('complaints.show', $this->complaint));
    }
}

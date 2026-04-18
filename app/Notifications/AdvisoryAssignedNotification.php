<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdvisoryAssignedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly AdvisoryRequest $advisoryRequest,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'advisory.assigned', [
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'assigned_by_id' => $this->assignedBy->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'advisory.assigned',
            'title' => 'Advisory request assigned',
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'request_number' => $this->advisoryRequest->request_number,
            'subject' => $this->advisoryRequest->subject,
            'assigned_by' => $this->assignedBy->name,
            'url' => route('advisory.show', $this->advisoryRequest),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Advisory assignment: {$this->advisoryRequest->request_number}")
            ->line("You have been assigned advisory request {$this->advisoryRequest->request_number}.")
            ->line($this->advisoryRequest->subject)
            ->action('Open request', route('advisory.show', $this->advisoryRequest));
    }
}

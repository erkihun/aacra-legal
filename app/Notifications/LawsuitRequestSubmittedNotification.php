<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LawsuitFilingRequest;
use App\Models\User;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LawsuitRequestSubmittedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly LawsuitFilingRequest $lawsuitRequest,
        public readonly User $submittedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'lawsuit-request.submitted', [
            'lawsuit_request_id' => $this->lawsuitRequest->getKey(),
            'submitted_by_id' => $this->submittedBy->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lawsuit-request.submitted',
            'title' => 'New lawsuit filing request submitted',
            'lawsuit_request_id' => $this->lawsuitRequest->getKey(),
            'request_code' => $this->lawsuitRequest->request_code,
            'subject' => $this->lawsuitRequest->subject,
            'submitted_by' => $this->submittedBy->name,
            'url' => route('lawsuit-requests.show', $this->lawsuitRequest),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New lawsuit filing request: {$this->lawsuitRequest->request_code}")
            ->line("A new lawsuit filing request {$this->lawsuitRequest->request_code} has been submitted.")
            ->line($this->lawsuitRequest->subject)
            ->line("Submitted by: {$this->submittedBy->name}")
            ->action('Open request', route('lawsuit-requests.show', $this->lawsuitRequest));
    }
}

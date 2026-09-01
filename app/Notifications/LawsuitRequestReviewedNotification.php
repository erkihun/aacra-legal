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

class LawsuitRequestReviewedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly LawsuitFilingRequest $lawsuitRequest,
        public readonly User $reviewedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'lawsuit-request.reviewed', [
            'lawsuit_request_id' => $this->lawsuitRequest->getKey(),
            'reviewed_by_id' => $this->reviewedBy->getKey(),
            'status' => $this->lawsuitRequest->status?->value,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lawsuit-request.reviewed',
            'title' => 'Lawsuit filing request reviewed',
            'lawsuit_request_id' => $this->lawsuitRequest->getKey(),
            'request_code' => $this->lawsuitRequest->request_code,
            'subject' => $this->lawsuitRequest->subject,
            'status' => $this->lawsuitRequest->status?->value,
            'reviewed_by' => $this->reviewedBy->name,
            'url' => route('lawsuit-requests.show', $this->lawsuitRequest),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Lawsuit filing request reviewed: {$this->lawsuitRequest->request_code}")
            ->line("Your lawsuit filing request {$this->lawsuitRequest->request_code} has been reviewed.")
            ->line("Status: {$this->lawsuitRequest->status?->value}")
            ->action('Open request', route('lawsuit-requests.show', $this->lawsuitRequest));
    }
}

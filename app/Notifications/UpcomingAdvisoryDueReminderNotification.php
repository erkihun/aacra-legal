<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdvisoryRequest;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingAdvisoryDueReminderNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public readonly string $sentForDate;

    public function __construct(
        public readonly AdvisoryRequest $advisoryRequest,
        ?string $sentForDate = null,
    ) {
        $this->sentForDate = $sentForDate ?? now()->toDateString();
    }

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'advisory.due_soon', [
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'due_date' => $this->advisoryRequest->due_date?->toDateString(),
            'sent_for_date' => $this->sentForDate,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'advisory.due_soon',
            'title' => 'Upcoming advisory due date',
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'request_number' => $this->advisoryRequest->request_number,
            'due_date' => optional($this->advisoryRequest->due_date)->toDateString(),
            'url' => route('advisory.show', $this->advisoryRequest),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upcoming advisory due date: {$this->advisoryRequest->request_number}")
            ->line('An advisory request assigned to you is approaching its due date.')
            ->action('Open request', route('advisory.show', $this->advisoryRequest));
    }
}

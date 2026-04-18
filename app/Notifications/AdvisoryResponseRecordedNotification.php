<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdvisoryRequest;
use App\Models\AdvisoryResponse;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdvisoryResponseRecordedNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public function __construct(
        public readonly AdvisoryRequest $advisoryRequest,
        public readonly AdvisoryResponse $advisoryResponse,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function dedupeFingerprint(object $notifiable, string $channel): string
    {
        return $this->buildDedupeFingerprint($notifiable, $channel, 'advisory.response_recorded', [
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'advisory_response_id' => $this->advisoryResponse->getKey(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'advisory.response_recorded',
            'title' => 'Advisory response received',
            'request_number' => $this->advisoryRequest->request_number,
            'subject' => $this->advisoryRequest->subject,
            'response_subject' => $this->advisoryResponse->subject ?? $this->advisoryResponse->summary,
            'responder_name' => $this->advisoryResponse->responder?->name,
            'responded_at' => $this->advisoryResponse->responded_at?->toIso8601String(),
            'advisory_request_id' => $this->advisoryRequest->getKey(),
            'advisory_response_id' => $this->advisoryResponse->getKey(),
            'url' => route('advisory.responses.show', [
                'advisoryRequest' => $this->advisoryRequest,
                'advisoryResponse' => $this->advisoryResponse,
            ]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $responseSubject = $this->advisoryResponse->subject ?? $this->advisoryResponse->summary ?? $this->advisoryRequest->subject;

        return (new MailMessage)
            ->subject("Advisory response received: {$this->advisoryRequest->request_number}")
            ->line("Your advisory request {$this->advisoryRequest->request_number} has received a response.")
            ->line("Request subject: {$this->advisoryRequest->subject}")
            ->line("Response subject: {$responseSubject}")
            ->line('Responder: '.($this->advisoryResponse->responder?->name ?? ''))
            ->line('Responded at: '.($this->advisoryResponse->responded_at?->toDateTimeString() ?? ''))
            ->action('Open response', route('advisory.responses.show', [
                'advisoryRequest' => $this->advisoryRequest,
                'advisoryResponse' => $this->advisoryResponse,
            ]));
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AdvisoryRequest;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(public readonly AdvisoryRequest $advisoryRequest) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'advisory.overdue',
            'title' => 'Overdue advisory request',
            'request_number' => $this->advisoryRequest->request_number,
            'due_date' => optional($this->advisoryRequest->due_date)->toDateString(),
            'url' => route('advisory.show', $this->advisoryRequest),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Overdue advisory request: {$this->advisoryRequest->request_number}")
            ->line('An advisory request assigned to you is overdue.')
            ->action('Open request', route('advisory.show', $this->advisoryRequest));
    }
}

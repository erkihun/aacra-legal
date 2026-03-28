<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppealDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(public readonly LegalCase $legalCase) {}

    public function via(object $notifiable): array
    {
        return $this->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case.appeal_deadline',
            'title' => 'Appeal deadline reminder',
            'case_number' => $this->legalCase->case_number,
            'appeal_deadline' => $this->legalCase->appeal_deadline?->toDateString(),
            'url' => route('cases.show', $this->legalCase),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Appeal deadline: {$this->legalCase->case_number}")
            ->line('An appeal deadline is approaching for one of your legal cases.')
            ->line('Appeal deadline: '.$this->legalCase->appeal_deadline?->toFormattedDateString())
            ->action('Open case', route('cases.show', $this->legalCase));
    }
}

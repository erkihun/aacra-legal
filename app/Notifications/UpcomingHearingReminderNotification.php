<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingHearingReminderNotification extends Notification implements ShouldQueue
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
            'type' => 'case.upcoming_hearing',
            'title' => 'Upcoming hearing reminder',
            'case_number' => $this->legalCase->case_number,
            'next_hearing_date' => optional($this->legalCase->next_hearing_date)->toDateString(),
            'url' => route('cases.show', $this->legalCase),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upcoming hearing: {$this->legalCase->case_number}")
            ->line('A hearing is approaching for one of your assigned legal cases.')
            ->line('Next hearing date: '.optional($this->legalCase->next_hearing_date)->toFormattedDateString())
            ->action('Open case', route('cases.show', $this->legalCase));
    }
}

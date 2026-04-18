<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LegalCase;
use App\Notifications\Concerns\BuildsNotificationDedupeKey;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use App\Notifications\Contracts\DeduplicatesNotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingHearingReminderNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
{
    use Queueable;
    use BuildsNotificationDedupeKey;
    use ResolvesNotificationChannels;

    public readonly string $sentForDate;

    public function __construct(
        public readonly LegalCase $legalCase,
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
        return $this->buildDedupeFingerprint($notifiable, $channel, 'case.upcoming_hearing', [
            'legal_case_id' => $this->legalCase->getKey(),
            'next_hearing_date' => $this->legalCase->next_hearing_date?->toDateString(),
            'sent_for_date' => $this->sentForDate,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case.upcoming_hearing',
            'title' => 'Upcoming hearing reminder',
            'legal_case_id' => $this->legalCase->getKey(),
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

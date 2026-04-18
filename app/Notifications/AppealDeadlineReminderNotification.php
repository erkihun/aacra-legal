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

class AppealDeadlineReminderNotification extends Notification implements DeduplicatesNotificationDelivery, ShouldQueue
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
        return $this->buildDedupeFingerprint($notifiable, $channel, 'case.appeal_deadline', [
            'legal_case_id' => $this->legalCase->getKey(),
            'appeal_deadline' => $this->legalCase->appeal_deadline?->toDateString(),
            'sent_for_date' => $this->sentForDate,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case.appeal_deadline',
            'title' => 'Appeal deadline reminder',
            'legal_case_id' => $this->legalCase->getKey(),
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

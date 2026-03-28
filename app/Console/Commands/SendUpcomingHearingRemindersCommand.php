<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CaseStatus;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\UpcomingHearingReminderNotification;
use App\Services\SystemSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendUpcomingHearingRemindersCommand extends Command
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {
        parent::__construct();
    }

    protected $signature = 'legal:send-upcoming-hearing-reminders {--days=3 : Number of days ahead to inspect for hearings}';

    protected $description = 'Send queued reminders for upcoming legal case hearings';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: $this->settings->hearingReminderDays()));

        $cases = LegalCase::query()
            ->with(['directorReviewer', 'assignedTeamLeader', 'assignedLegalExpert'])
            ->whereBetween('next_hearing_date', [
                today()->toDateString(),
                today()->copy()->addDays($days)->toDateString(),
            ])
            ->whereNotIn('status', [CaseStatus::CLOSED, CaseStatus::REJECTED])
            ->get();

        $deliveries = 0;
        $failures = 0;

        foreach ($cases as $legalCase) {
            $recipients = collect([
                $legalCase->directorReviewer,
                $legalCase->assignedTeamLeader,
                $legalCase->assignedLegalExpert,
            ])->filter(fn (mixed $user): bool => $user instanceof User && $user->is_active)
                ->unique('id')
                ->values();

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new UpcomingHearingReminderNotification($legalCase));

                    $message = "Case {$legalCase->case_number} has a hearing on {$legalCase->next_hearing_date?->toDateString()}.";
                    $this->dispatchMobileChannels($recipient, $message);
                    $deliveries++;
                } catch (Throwable $exception) {
                    $failures++;

                    Log::error('Failed to queue upcoming hearing reminder.', [
                        'legal_case_id' => $legalCase->getKey(),
                        'case_number' => $legalCase->case_number,
                        'recipient_id' => $recipient->getKey(),
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Processed {$cases->count()} cases and queued {$deliveries} hearing reminder deliveries.");

        if ($failures > 0) {
            $this->warn("{$failures} hearing reminder deliveries failed. Review the application logs for details.");
        }

        return self::SUCCESS;
    }

    private function dispatchMobileChannels(User $user, string $message): void
    {
        if ($this->settings->notificationsEnabled('sms') && $user->phone !== null) {
            SendSmsMessageJob::dispatch($user->phone, $message);
        }

        if ($this->settings->notificationsEnabled('telegram') && filled($user->telegram_chat_id)) {
            SendTelegramMessageJob::dispatch($user->telegram_chat_id, $message);
        }
    }
}

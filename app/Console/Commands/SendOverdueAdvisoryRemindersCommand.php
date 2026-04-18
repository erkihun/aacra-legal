<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AdvisoryRequestStatus;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Notifications\OverdueRequestNotification;
use App\Notifications\UpcomingAdvisoryDueReminderNotification;
use App\Services\SystemSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOverdueAdvisoryRemindersCommand extends Command
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {
        parent::__construct();
    }

    protected $signature = 'legal:send-overdue-advisory-reminders {--days= : Lead days for due-soon reminders}';

    protected $description = 'Send queued reminders for overdue advisory requests';

    public function handle(): int
    {
        $reminderDays = (int) ($this->option('days') ?: $this->settings->advisoryDueReminderDays());
        $windowEnd = today()->addDays(max(1, $reminderDays))->toDateString();

        $overdueRequests = AdvisoryRequest::query()
            ->with(['requester', 'directorReviewer', 'assignedTeamLeader', 'assignedLegalExpert'])
            ->whereDate('due_date', '<', today()->toDateString())
            ->whereNotIn('status', [
                AdvisoryRequestStatus::RESPONDED,
                AdvisoryRequestStatus::CLOSED,
                AdvisoryRequestStatus::REJECTED,
            ])
            ->get();

        $upcomingRequests = AdvisoryRequest::query()
            ->with(['requester', 'directorReviewer', 'assignedTeamLeader', 'assignedLegalExpert'])
            ->whereDate('due_date', '>=', today()->toDateString())
            ->whereDate('due_date', '<=', $windowEnd)
            ->whereNotIn('status', [
                AdvisoryRequestStatus::RESPONDED,
                AdvisoryRequestStatus::CLOSED,
                AdvisoryRequestStatus::REJECTED,
            ])
            ->get();

        $deliveries = 0;
        $failures = 0;

        foreach ($overdueRequests as $advisoryRequest) {
            $recipients = collect([
                $advisoryRequest->requester,
                $advisoryRequest->directorReviewer,
                $advisoryRequest->assignedTeamLeader,
                $advisoryRequest->assignedLegalExpert,
            ]);

            foreach ($this->filterRecipients($recipients) as $recipient) {
                try {
                    $recipient->notify(new OverdueRequestNotification($advisoryRequest));

                    $message = "Advisory {$advisoryRequest->request_number} is overdue. Due date: {$advisoryRequest->due_date?->toDateString()}.";
                    $this->dispatchMobileChannels($recipient, $message);
                    $deliveries++;
                } catch (Throwable $exception) {
                    $failures++;

                    Log::error('Failed to queue overdue advisory reminder.', [
                        'advisory_request_id' => $advisoryRequest->getKey(),
                        'request_number' => $advisoryRequest->request_number,
                        'recipient_id' => $recipient->getKey(),
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        foreach ($upcomingRequests as $advisoryRequest) {
            $recipients = collect([
                $advisoryRequest->requester,
                $advisoryRequest->directorReviewer,
                $advisoryRequest->assignedTeamLeader,
                $advisoryRequest->assignedLegalExpert,
            ]);

            foreach ($this->filterRecipients($recipients) as $recipient) {
                try {
                    $recipient->notify(new UpcomingAdvisoryDueReminderNotification($advisoryRequest));

                    $message = "Advisory {$advisoryRequest->request_number} is due on {$advisoryRequest->due_date?->toDateString()}.";
                    $this->dispatchMobileChannels($recipient, $message);
                    $deliveries++;
                } catch (Throwable $exception) {
                    $failures++;

                    Log::error('Failed to queue advisory due reminder.', [
                        'advisory_request_id' => $advisoryRequest->getKey(),
                        'request_number' => $advisoryRequest->request_number,
                        'recipient_id' => $recipient->getKey(),
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $processedCount = $overdueRequests->count() + $upcomingRequests->count();

        $this->info("Processed {$processedCount} advisory requests and queued {$deliveries} reminder deliveries.");

        if ($failures > 0) {
            $this->warn("{$failures} overdue advisory reminder deliveries failed. Review the application logs for details.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, mixed>  $recipients
     * @return Collection<int, User>
     */
    private function filterRecipients(Collection $recipients): Collection
    {
        return $recipients
            ->filter(fn (mixed $user): bool => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values();
    }

    private function dispatchMobileChannels(User $user, string $message): void
    {
        if ($this->settings->notificationsEnabled('sms') && $user->phone !== null) {
            SendSmsMessageJob::dispatch(
                $user->phone,
                $message,
                implode('|', ['advisory.reminder', (string) $user->getKey(), $message, 'sms']),
            );
        }

        if ($this->settings->notificationsEnabled('telegram') && filled($user->telegram_chat_id)) {
            SendTelegramMessageJob::dispatch(
                $user->telegram_chat_id,
                $message,
                implode('|', ['advisory.reminder', (string) $user->getKey(), $message, 'telegram']),
            );
        }
    }
}

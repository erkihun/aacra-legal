<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AutoEscalateComplaintAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class EscalateOverdueComplaintsCommand extends Command
{
    protected $signature = 'complaints:escalate-overdue';

    protected $description = 'Automatically escalate overdue complaints to the complaint committee';

    public function __construct(
        private readonly AutoEscalateComplaintAction $action,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $processed = 0;
        $failures = 0;

        foreach ($this->action->overdueComplaints() as $complaint) {
            try {
                $this->action->execute($complaint);
                $processed++;
            } catch (Throwable $exception) {
                $failures++;

                Log::error('Failed to auto-escalate complaint.', [
                    'complaint_id' => $complaint->getKey(),
                    'complaint_number' => $complaint->complaint_number,
                    'exception' => $exception::class,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Auto-escalated {$processed} overdue complaints.");

        if ($failures > 0) {
            $this->warn("{$failures} complaint escalations failed. Review the application logs for details.");
        }

        return self::SUCCESS;
    }
}

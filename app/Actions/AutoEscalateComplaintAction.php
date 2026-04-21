<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintEscalationType;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintEscalatedNotification;
use App\Services\SystemSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AutoEscalateComplaintAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function execute(Complaint $complaint): Complaint
    {
        return DB::transaction(function () use ($complaint): Complaint {
            if ($complaint->is_escalated || $complaint->department_responded_at !== null || $complaint->isClosed()) {
                return $complaint;
            }

            $previousStatus = $complaint->status;

            $complaint->update([
                'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
                'is_overdue' => true,
                'is_escalated' => true,
                'is_auto_escalated' => true,
                'forwarded_to_committee_at' => now(),
            ]);

            $complaint->escalations()->firstOrCreate(
                ['escalation_type' => ComplaintEscalationType::AUTO],
                [
                    'escalated_by_id' => null,
                    'reason' => 'Auto-escalated after the department response deadline expired.',
                    'escalated_at' => now(),
                ],
            );

            $complaint->histories()->create([
                'actor_id' => null,
                'from_status' => $previousStatus,
                'to_status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
                'action' => 'auto_escalated',
                'notes' => 'Complaint auto-escalated after deadline.',
                'acted_at' => now(),
            ]);

            $recipients = $this->committeeRecipients();
            $complaint->loadMissing('complainant');

            DB::afterCommit(function () use ($complaint, $recipients): void {
                foreach ($recipients as $recipient) {
                    $recipient->notify(new ComplaintEscalatedNotification($complaint, ComplaintEscalationType::AUTO));
                }

                if ($complaint->complainant !== null) {
                    $complaint->complainant->notify(new ComplaintEscalatedNotification($complaint, ComplaintEscalationType::AUTO));
                }
            });

            return $complaint->fresh(['escalations']);
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function overdueComplaints(): Collection
    {
        if (! $this->settings->complaintAutoEscalationEnabled()) {
            return collect();
        }

        return Complaint::query()
            ->whereNotIn('status', [ComplaintStatus::ESCALATED_TO_COMMITTEE, ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED])
            ->where('is_escalated', false)
            ->whereNull('department_responded_at')
            ->whereNotNull('department_response_deadline_at')
            ->where('department_response_deadline_at', '<', now())
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function committeeRecipients(): Collection
    {
        $configuredIds = $this->settings->complaintCommitteeRecipientIds();

        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($configuredIds): void {
                $query->whereIn('id', $configuredIds)
                    ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->whereIn('name', ['complaints.committee.review', 'complaints.committee.decide']))
                    ->orWhereHas('roles.permissions', fn ($permissionQuery) => $permissionQuery->whereIn('name', ['complaints.committee.review', 'complaints.committee.decide']));
            })
            ->get()
            ->unique('id')
            ->values();
    }
}

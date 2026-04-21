<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintEscalationType;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintEscalatedNotification;
use App\Services\SystemSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForwardComplaintToCommitteeAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Complaint $complaint, array $attributes, User $actor): Complaint
    {
        return DB::transaction(function () use ($complaint, $attributes, $actor): Complaint {
            if ($complaint->is_escalated || $complaint->forwarded_to_committee_at !== null) {
                throw ValidationException::withMessages([
                    'dissatisfaction_reason' => __('This complaint has already been forwarded to the committee.'),
                ]);
            }

            $previousStatus = $complaint->status;

            $complaint->update([
                'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
                'is_dissatisfied' => true,
                'is_escalated' => true,
                'dissatisfaction_reason' => trim((string) $attributes['dissatisfaction_reason']),
                'forwarded_to_committee_at' => now(),
            ]);

            $complaint->escalations()->create([
                'escalated_by_id' => $actor->getKey(),
                'escalation_type' => ComplaintEscalationType::DISSATISFACTION,
                'reason' => $complaint->dissatisfaction_reason,
                'escalated_at' => now(),
            ]);

            $complaint->histories()->create([
                'actor_id' => $actor->getKey(),
                'from_status' => $previousStatus,
                'to_status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
                'action' => 'forwarded_to_committee',
                'notes' => $complaint->dissatisfaction_reason,
                'acted_at' => now(),
            ]);

            $recipients = $this->committeeRecipients();

            DB::afterCommit(function () use ($complaint, $recipients): void {
                foreach ($recipients as $recipient) {
                    $recipient->notify(new ComplaintEscalatedNotification($complaint, ComplaintEscalationType::DISSATISFACTION));
                }
            });

            return $complaint->fresh(['escalations.escalatedBy']);
        });
    }

    private function committeeRecipients()
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

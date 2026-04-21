<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintCommitteeDecisionIssuedNotification;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordComplaintCommitteeDecisionAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(Complaint $complaint, array $attributes, User $actor, array $attachments = []): Complaint
    {
        return DB::transaction(function () use ($complaint, $attributes, $actor, $attachments): Complaint {
            if ($complaint->committeeDecisions()->exists() || $complaint->committee_decision_at !== null) {
                throw ValidationException::withMessages([
                    'decision_detail' => __('A committee decision has already been recorded for this complaint.'),
                ]);
            }

            $decision = $complaint->committeeDecisions()->create([
                'committee_actor_id' => $actor->getKey(),
                'investigation_notes' => $this->richTextSanitizer->sanitize((string) ($attributes['investigation_notes'] ?? '')),
                'decision_summary' => trim((string) $attributes['decision_summary']),
                'decision_detail' => $this->richTextSanitizer->sanitize((string) $attributes['decision_detail']),
                'decision_date' => now(),
                'outcome' => $attributes['outcome'],
            ]);

            if ($attachments !== []) {
                $this->storeAttachmentAction->execute($decision, $attachments, $actor);
            }

            $previousStatus = $complaint->status;

            $complaint->update([
                'status' => ComplaintStatus::RESOLVED,
                'committee_review_started_at' => $complaint->committee_review_started_at ?? now(),
                'committee_decision_at' => now(),
                'resolved_at' => now(),
                'closed_at' => now(),
            ]);

            $complaint->histories()->create([
                'actor_id' => $actor->getKey(),
                'from_status' => $previousStatus,
                'to_status' => ComplaintStatus::RESOLVED,
                'action' => 'committee_decision_recorded',
                'notes' => $decision->decision_summary,
                'acted_at' => now(),
                'metadata' => [
                    'outcome' => $decision->outcome?->value,
                ],
            ]);

            $complaint->loadMissing('complainant', 'department');
            $departmentRecipients = User::query()
                ->where('is_active', true)
                ->where('department_id', $complaint->department_id)
                ->withAnyPermission(['complaints.view_department', 'complaints.respond_department'])
                ->get()
                ->unique('id')
                ->values();

            DB::afterCommit(function () use ($complaint, $decision, $departmentRecipients): void {
                if ($complaint->complainant !== null) {
                    $complaint->complainant->notify(new ComplaintCommitteeDecisionIssuedNotification($complaint, $decision));
                }

                foreach ($departmentRecipients as $recipient) {
                    $recipient->notify(new ComplaintCommitteeDecisionIssuedNotification($complaint, $decision));
                }
            });

            return $complaint->fresh(['committeeDecisions.committeeActor', 'committeeDecisions.attachments.uploadedBy']);
        });
    }
}

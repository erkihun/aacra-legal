<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintDepartmentResponseRecordedNotification;
use App\Support\RichTextSanitizer;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class RecordComplaintDepartmentResponseAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(Complaint $complaint, array $attributes, User $responder, array $attachments = []): Complaint
    {
        return DB::transaction(function () use ($complaint, $attributes, $responder, $attachments): Complaint {
            if ($complaint->responses()->exists()) {
                throw ValidationException::withMessages([
                    'response_content' => __('A department response has already been recorded for this complaint.'),
                ]);
            }

            $response = $complaint->responses()->create([
                'responder_id' => $responder->getKey(),
                'responder_department_id' => $responder->department_id,
                'subject' => trim((string) $attributes['subject']),
                'response_content' => $this->richTextSanitizer->sanitize((string) $attributes['response_content']),
                'responded_at' => now(),
            ]);

            if ($attachments !== []) {
                $this->storeAttachmentAction->execute($response, $attachments, $responder);
            }

            $previousStatus = $complaint->status;

            $complaint->update([
                'status' => ComplaintStatus::DEPARTMENT_RESPONDED,
                'department_responded_at' => now(),
                'is_overdue' => false,
            ]);

            $complaint->histories()->create([
                'actor_id' => $responder->getKey(),
                'from_status' => $previousStatus,
                'to_status' => ComplaintStatus::DEPARTMENT_RESPONDED,
                'action' => 'department_response_recorded',
                'notes' => 'Department response recorded.',
                'acted_at' => now(),
            ]);

            $complaint->loadMissing('complainant');

            DB::afterCommit(function () use ($complaint, $response): void {
                if ($complaint->complainant !== null) {
                    $complaint->complainant->notify(new ComplaintDepartmentResponseRecordedNotification($complaint, $response));
                }
            });

            return $complaint->fresh(['responses.responder', 'responses.attachments.uploadedBy']);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestType;
use App\Enums\AdvisoryRequestStatus;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAdvisoryResponseAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(AdvisoryRequest $advisoryRequest, array $attributes, User $expert, array $attachments = []): AdvisoryRequest
    {
        if ($advisoryRequest->assigned_legal_expert_id !== $expert->getKey() && ! $expert->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'assigned_legal_expert_id' => __('Only the assigned expert can record a response.'),
            ]);
        }

        if (! in_array($advisoryRequest->status, [
            AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
            AdvisoryRequestStatus::IN_PROGRESS,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('The advisory request is not ready for a response.'),
            ]);
        }

        return DB::transaction(function () use ($advisoryRequest, $attributes, $expert, $attachments): AdvisoryRequest {
            $subject = trim((string) ($attributes['subject'] ?? ''));
            $responseBody = $this->richTextSanitizer->sanitize($attributes['response'] ?? null);

            $response = $advisoryRequest->responses()->create([
                'responder_id' => $expert->getKey(),
                'subject' => $subject,
                'response' => $responseBody,
                'response_type' => $advisoryRequest->request_type?->value ?? AdvisoryRequestType::WRITTEN->value,
                'summary' => $subject,
                'advice_text' => $responseBody,
                'follow_up_notes' => null,
                'responded_at' => now(),
            ]);

            if ($attachments !== []) {
                $this->storeAttachmentAction->execute($response, $attachments, $expert);
            }

            $advisoryRequest->update([
                'internal_summary' => $response->subject,
                'status' => AdvisoryRequestStatus::RESPONDED,
                'workflow_stage' => WorkflowStage::COMPLETED,
                'completed_at' => now(),
            ]);

            return $advisoryRequest->fresh();
        });
    }
}

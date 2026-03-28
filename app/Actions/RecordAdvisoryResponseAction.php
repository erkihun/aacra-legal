<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAdvisoryResponseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(AdvisoryRequest $advisoryRequest, array $attributes, User $expert): AdvisoryRequest
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

        return DB::transaction(function () use ($advisoryRequest, $attributes, $expert): AdvisoryRequest {
            $response = $advisoryRequest->responses()->create([
                'responder_id' => $expert->getKey(),
                'response_type' => $attributes['response_type'],
                'summary' => $attributes['summary'],
                'advice_text' => $attributes['advice_text'] ?? null,
                'follow_up_notes' => $attributes['follow_up_notes'] ?? null,
                'responded_at' => now(),
            ]);

            $advisoryRequest->update([
                'internal_summary' => $response->summary,
                'status' => AdvisoryRequestStatus::RESPONDED,
                'workflow_stage' => WorkflowStage::COMPLETED,
                'completed_at' => now(),
            ]);

            return $advisoryRequest->fresh();
        });
    }
}

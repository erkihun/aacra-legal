<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateReturnedAdvisoryRequestAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(AdvisoryRequest $advisoryRequest, array $attributes, User $requester): AdvisoryRequest
    {
        if ($advisoryRequest->requester_user_id !== $requester->getKey() || $advisoryRequest->status !== AdvisoryRequestStatus::RETURNED) {
            throw ValidationException::withMessages([
                'status' => __('The advisory request is not ready for requester resubmission.'),
            ]);
        }

        return DB::transaction(function () use ($advisoryRequest, $attributes, $requester): AdvisoryRequest {
            $advisoryRequest->update([
                'department_id' => $attributes['department_id'],
                'category_id' => $attributes['category_id'],
                'subject' => $attributes['subject'],
                'request_type' => $attributes['request_type'],
                'priority' => $attributes['priority'],
                'description' => $attributes['description'],
                'due_date' => $attributes['due_date'] ?? null,
                'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
                'director_reviewer_id' => null,
                'assigned_team_leader_id' => null,
                'assigned_legal_expert_id' => null,
                'completed_at' => null,
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($advisoryRequest, $attributes['attachments'], $requester);
            }

            activity()
                ->performedOn($advisoryRequest)
                ->causedBy($requester)
                ->event('resubmitted')
                ->withProperties([
                    'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW->value,
                ])
                ->log('Requester resubmitted advisory request');

            return $advisoryRequest->fresh([
                'department',
                'category',
                'requester',
                'attachments',
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\DirectorDecision;
use App\Enums\SystemRole;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Events\AdvisoryAssigned;
use App\Models\AdvisoryAssignment;
use App\Models\AdvisoryRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectorReviewAdvisoryAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(AdvisoryRequest $advisoryRequest, array $attributes, User $director): AdvisoryRequest
    {
        if ($advisoryRequest->status !== AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW && $advisoryRequest->status !== AdvisoryRequestStatus::RETURNED) {
            throw ValidationException::withMessages([
                'status' => __('The advisory request is not awaiting director review.'),
            ]);
        }

        return DB::transaction(function () use ($advisoryRequest, $attributes, $director): AdvisoryRequest {
            $decision = DirectorDecision::from($attributes['director_decision']);

            $updates = [
                'director_reviewer_id' => $director->getKey(),
                'director_decision' => $decision,
                'director_notes' => $attributes['director_notes'] ?? null,
            ];

            if ($decision === DirectorDecision::APPROVED) {
                if ($advisoryRequest->assigned_team_leader_id !== null) {
                    throw ValidationException::withMessages([
                        'assigned_team_leader_id' => __('A team leader has already been assigned to this advisory request.'),
                    ]);
                }

                /** @var User $teamLeader */
                $teamLeader = User::query()->with('team')->findOrFail($attributes['assigned_team_leader_id']);

                if (
                    ! $teamLeader->is_active
                    || ! $teamLeader->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER)
                    || $teamLeader->team?->type !== TeamType::ADVISORY
                ) {
                    throw ValidationException::withMessages([
                        'assigned_team_leader_id' => __('The selected advisory team leader is invalid.'),
                    ]);
                }

                $updates += [
                    'assigned_team_leader_id' => $teamLeader->getKey(),
                    'status' => AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
                    'workflow_stage' => WorkflowStage::TEAM_LEADER,
                ];

                $advisoryRequest->update($updates);

                AdvisoryAssignment::query()->create([
                    'advisory_request_id' => $advisoryRequest->getKey(),
                    'assigned_by_id' => $director->getKey(),
                    'assigned_to_id' => $teamLeader->getKey(),
                    'assignment_role' => 'team_leader',
                    'notes' => $attributes['director_notes'] ?? null,
                    'assigned_at' => now(),
                ]);

                AdvisoryAssigned::dispatch($advisoryRequest->fresh(), $teamLeader, $director);

                return $advisoryRequest->fresh();
            }

            if ($decision === DirectorDecision::RETURNED) {
                $updates += [
                    'status' => AdvisoryRequestStatus::RETURNED,
                    'workflow_stage' => WorkflowStage::REQUESTER,
                ];
            }

            if ($decision === DirectorDecision::REJECTED) {
                $updates += [
                    'status' => AdvisoryRequestStatus::REJECTED,
                    'workflow_stage' => WorkflowStage::COMPLETED,
                    'completed_at' => now(),
                ];
            }

            $advisoryRequest->update($updates);

            return $advisoryRequest->fresh();
        });
    }
}

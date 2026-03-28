<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Events\AdvisoryAssigned;
use App\Models\AdvisoryAssignment;
use App\Models\AdvisoryRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignAdvisoryToExpertAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(AdvisoryRequest $advisoryRequest, array $attributes, User $actor): AdvisoryRequest
    {
        if (! in_array($advisoryRequest->status, [
            AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
            AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
            AdvisoryRequestStatus::IN_PROGRESS,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('The advisory request cannot be assigned to an expert at its current stage.'),
            ]);
        }

        /** @var User $expert */
        $expert = User::query()->with('team')->findOrFail($attributes['assigned_legal_expert_id']);
        $expectedTeamId = $advisoryRequest->assignedTeamLeader?->team_id ?? $actor->team_id;

        if (
            ! $expert->is_active
            || ! $expert->hasSystemRole(SystemRole::LEGAL_EXPERT)
            || $expert->team?->type !== TeamType::ADVISORY
            || ($expectedTeamId !== null && $expert->team_id !== $expectedTeamId)
        ) {
            throw ValidationException::withMessages([
                'assigned_legal_expert_id' => __('The selected advisory expert is invalid.'),
            ]);
        }

        return DB::transaction(function () use ($advisoryRequest, $attributes, $actor, $expert): AdvisoryRequest {
            $advisoryRequest->update([
                'assigned_legal_expert_id' => $expert->getKey(),
                'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
                'workflow_stage' => WorkflowStage::EXPERT,
            ]);

            AdvisoryAssignment::query()->create([
                'advisory_request_id' => $advisoryRequest->getKey(),
                'assigned_by_id' => $actor->getKey(),
                'assigned_to_id' => $expert->getKey(),
                'assignment_role' => 'expert',
                'notes' => $attributes['notes'] ?? null,
                'assigned_at' => now(),
            ]);

            AdvisoryAssigned::dispatch($advisoryRequest->fresh(), $expert, $actor);

            return $advisoryRequest->fresh();
        });
    }
}

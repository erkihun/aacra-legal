<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Events\CaseAssigned;
use App\Models\CaseAssignment;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignCaseToExpertAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes, User $actor): LegalCase
    {
        if ($legalCase->isClosed()) {
            throw ValidationException::withMessages([
                'status' => __('Closed cases cannot be assigned.'),
            ]);
        }

        if (! in_array($legalCase->status, [
            CaseStatus::ASSIGNED_TO_TEAM_LEADER,
            CaseStatus::ASSIGNED_TO_EXPERT,
            CaseStatus::IN_PROGRESS,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('The case cannot be assigned at its current stage.'),
            ]);
        }

        if ($legalCase->assigned_legal_expert_id !== null) {
            throw ValidationException::withMessages([
                'assigned_legal_expert_id' => __('A legal expert has already been assigned to this case.'),
            ]);
        }

        /** @var User $expert */
        $expert = User::query()->with('team')->findOrFail($attributes['assigned_legal_expert_id']);
        $expectedTeamId = $legalCase->assignedTeamLeader?->team_id ?? $actor->team_id;

        if (
            ! $expert->is_active
            || ! $expert->canHandleAssignedCases()
            || $expert->team?->type !== TeamType::LITIGATION
            || ($expectedTeamId !== null && $expert->team_id !== $expectedTeamId)
        ) {
            throw ValidationException::withMessages([
                'assigned_legal_expert_id' => __('The selected litigation expert is invalid.'),
            ]);
        }

        return DB::transaction(function () use ($legalCase, $attributes, $actor, $expert): LegalCase {
            $legalCase->update([
                'assigned_legal_expert_id' => $expert->getKey(),
                'status' => CaseStatus::ASSIGNED_TO_EXPERT,
                'workflow_stage' => WorkflowStage::EXPERT,
            ]);

            CaseAssignment::query()->create([
                'legal_case_id' => $legalCase->getKey(),
                'assigned_by_id' => $actor->getKey(),
                'assigned_to_id' => $expert->getKey(),
                'assignment_role' => 'expert',
                'notes' => $attributes['notes'] ?? null,
                'assigned_at' => now(),
            ]);

            CaseAssigned::dispatch($legalCase->fresh(), $expert, $actor);

            return $legalCase->fresh();
        });
    }
}

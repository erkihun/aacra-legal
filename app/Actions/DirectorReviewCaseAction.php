<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Events\CaseAssigned;
use App\Models\CaseAssignment;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectorReviewCaseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes, User $director): LegalCase
    {
        if ($legalCase->isClosed()) {
            throw ValidationException::withMessages([
                'status' => __('Closed cases cannot be reviewed.'),
            ]);
        }

        if (! in_array($legalCase->status, [CaseStatus::UNDER_DIRECTOR_REVIEW, CaseStatus::INTAKE], true)) {
            throw ValidationException::withMessages([
                'status' => __('The case is not awaiting director review.'),
            ]);
        }

        return DB::transaction(function () use ($legalCase, $attributes, $director): LegalCase {
            $decision = DirectorDecision::from($attributes['director_decision']);

            $updates = [
                'director_reviewer_id' => $director->getKey(),
                'director_decision' => $decision,
                'director_notes' => $attributes['director_notes'] ?? null,
            ];

            if ($decision === DirectorDecision::APPROVED) {
                if ($legalCase->assigned_team_leader_id !== null) {
                    throw ValidationException::withMessages([
                        'assigned_team_leader_id' => __('A team leader has already been assigned to this case.'),
                    ]);
                }

                /** @var User $teamLeader */
                $teamLeader = User::query()->with('team')->findOrFail($attributes['assigned_team_leader_id']);

                if (
                    ! $teamLeader->is_active
                    || ! $teamLeader->canLeadLitigationWorkflow()
                    || $teamLeader->team?->type !== TeamType::LITIGATION
                ) {
                    throw ValidationException::withMessages([
                        'assigned_team_leader_id' => __('The selected litigation team leader is invalid.'),
                    ]);
                }

                $updates += [
                    'assigned_team_leader_id' => $teamLeader->getKey(),
                    'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
                    'workflow_stage' => WorkflowStage::TEAM_LEADER,
                ];

                $legalCase->update($updates);

                CaseAssignment::query()->create([
                    'legal_case_id' => $legalCase->getKey(),
                    'assigned_by_id' => $director->getKey(),
                    'assigned_to_id' => $teamLeader->getKey(),
                    'assignment_role' => 'team_leader',
                    'notes' => $attributes['director_notes'] ?? null,
                    'assigned_at' => now(),
                ]);

                CaseAssigned::dispatch($legalCase->fresh(), $teamLeader, $director);

                return $legalCase->fresh();
            }

            if ($decision === DirectorDecision::RETURNED) {
                $updates += [
                    'status' => CaseStatus::INTAKE,
                    'workflow_stage' => WorkflowStage::REGISTRAR,
                ];
            }

            if ($decision === DirectorDecision::REJECTED) {
                $updates += [
                    'status' => CaseStatus::REJECTED,
                    'workflow_stage' => WorkflowStage::COMPLETED,
                    'completed_at' => now(),
                ];
            }

            $legalCase->update($updates);

            return $legalCase->fresh();
        });
    }
}

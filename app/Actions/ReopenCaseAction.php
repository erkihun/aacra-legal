<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\WorkflowStage;
use App\Models\LegalCase;
use App\Models\User;

class ReopenCaseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes, User $actor): LegalCase
    {
        $legalCase->update([
            'status' => $this->restoreStatus($legalCase),
            'workflow_stage' => $this->restoreWorkflowStage($legalCase),
            'completed_at' => null,
            'reopened_at' => now(),
            'reopened_by_id' => $actor->getKey(),
            'reopen_reason' => $attributes['reopen_reason'],
        ]);

        return $legalCase->fresh();
    }

    private function restoreStatus(LegalCase $legalCase): CaseStatus
    {
        if ($legalCase->assigned_legal_expert_id !== null) {
            return CaseStatus::IN_PROGRESS;
        }

        if ($legalCase->assigned_team_leader_id !== null) {
            return CaseStatus::ASSIGNED_TO_TEAM_LEADER;
        }

        return CaseStatus::UNDER_DIRECTOR_REVIEW;
    }

    private function restoreWorkflowStage(LegalCase $legalCase): WorkflowStage
    {
        if ($legalCase->assigned_legal_expert_id !== null) {
            return WorkflowStage::EXPERT;
        }

        if ($legalCase->assigned_team_leader_id !== null) {
            return WorkflowStage::TEAM_LEADER;
        }

        return WorkflowStage::DIRECTOR;
    }
}

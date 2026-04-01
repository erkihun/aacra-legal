<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\WorkflowStage;
use App\Models\LegalCase;
use Illuminate\Validation\ValidationException;

class CloseCaseAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes): LegalCase
    {
        if ($legalCase->isClosed()) {
            throw ValidationException::withMessages([
                'status' => __('The case is already closed.'),
            ]);
        }

        if (! in_array($legalCase->status, [
            CaseStatus::ASSIGNED_TO_EXPERT,
            CaseStatus::IN_PROGRESS,
            CaseStatus::AWAITING_DECISION,
            CaseStatus::DECIDED,
            CaseStatus::APPEALED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('The case is not ready for closure.'),
            ]);
        }

        if (blank($attributes['outcome'] ?? $legalCase->outcome)) {
            throw ValidationException::withMessages([
                'outcome' => __('A case outcome is required before closure.'),
            ]);
        }

        $legalCase->update([
            'outcome' => $attributes['outcome'] ?? $legalCase->outcome,
            'decision_date' => $attributes['decision_date'] ?? $legalCase->decision_date,
            'appeal_deadline' => $attributes['appeal_deadline'] ?? $legalCase->appeal_deadline,
            'status' => CaseStatus::CLOSED,
            'workflow_stage' => WorkflowStage::COMPLETED,
            'completed_at' => now(),
        ]);

        return $legalCase->fresh();
    }
}

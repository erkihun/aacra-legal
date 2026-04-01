<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCaseHearingAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes, User $expert): LegalCase
    {
        if (! in_array($legalCase->status, [
            CaseStatus::ASSIGNED_TO_EXPERT,
            CaseStatus::IN_PROGRESS,
            CaseStatus::AWAITING_DECISION,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => __('The case is not ready for a hearing record.'),
            ]);
        }

        return DB::transaction(function () use ($legalCase, $attributes, $expert): LegalCase {
            $legalCase->hearings()->create([
                'recorded_by_id' => $expert->getKey(),
                'hearing_date' => $attributes['hearing_date'],
                'next_hearing_date' => $attributes['next_hearing_date'] ?? null,
                'appearance_status' => $attributes['appearance_status'] ?? null,
                'summary' => $attributes['summary'],
                'court_decision' => $attributes['court_decision'] ?? null,
            ]);

            $legalCase->update([
                'next_hearing_date' => $attributes['next_hearing_date'] ?? null,
                'status' => filled($attributes['court_decision'] ?? null) && empty($attributes['next_hearing_date'])
                    ? CaseStatus::DECIDED
                    : CaseStatus::IN_PROGRESS,
            ]);

            return $legalCase->fresh();
        });
    }
}

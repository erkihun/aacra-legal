<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\LegalCase;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class UpdateLegalCaseAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LegalCase $legalCase, array $attributes, User $user): LegalCase
    {
        return DB::transaction(function () use ($legalCase, $attributes, $user): LegalCase {
            $legalCase->update([
                'case_number' => $attributes['case_number'],
                'court_id' => $attributes['court_id'] ?? null,
                'case_type_id' => $attributes['case_type_id'] ?? null,
                'main_case_type' => $attributes['main_case_type'],
                'plaintiff' => $attributes['plaintiff'] ?? null,
                'defendant' => $attributes['defendant'] ?? null,
                'status' => $attributes['status'],
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
                'director_reviewer_id' => null,
                'assigned_team_leader_id' => null,
                'assigned_legal_expert_id' => null,
                'claim_summary' => $this->richTextSanitizer->sanitize($attributes['claim_summary']),
                'amount' => $attributes['amount'] ?? null,
                'crime_scene' => $attributes['crime_scene'] ?? null,
                'police_station' => $attributes['police_station'] ?? null,
                'stolen_property_type' => $attributes['stolen_property_type'] ?? null,
                'stolen_property_estimated_value' => $attributes['stolen_property_estimated_value'] ?? null,
                'suspect_names' => $attributes['suspect_names'] ?? null,
                'statement_date' => $attributes['statement_date'] ?? null,
                'filing_date' => $attributes['filing_date'] ?? $legalCase->filing_date,
                'next_hearing_date' => $attributes['next_hearing_date'] ?? null,
                'priority' => $attributes['priority'],
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($legalCase, $attributes['attachments'], $user);
            }

            activity()
                ->performedOn($legalCase)
                ->causedBy($user)
                ->event('updated')
                ->log('Registrar updated legal case');

            return $legalCase->fresh([
                'court',
                'caseType',
                'registeredBy',
                'attachments.uploadedBy',
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\CaseType;
use App\Models\LegalCase;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class OpenLegalCaseAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $sequenceNumberAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $registrar): LegalCase
    {
        return DB::transaction(function () use ($attributes, $registrar): LegalCase {
            $mainCaseType = $attributes['main_case_type'];
            $caseTypeId = $attributes['case_type_id'] ?? null;

            if ($mainCaseType === 'labour-dispute' && $caseTypeId === null) {
                $caseTypeId = CaseType::query()->where('code', 'LAB')->value('id');
            }

            $legalCase = LegalCase::query()->create([
                'case_number' => $attributes['case_number'] ?: $this->sequenceNumberAction->execute('CASE'),
                'court_id' => $attributes['court_id'] ?? null,
                'case_type_id' => $caseTypeId,
                'main_case_type' => $mainCaseType,
                'registered_by_id' => $registrar->getKey(),
                'plaintiff' => $attributes['plaintiff'] ?? null,
                'defendant' => $attributes['defendant'] ?? null,
                'status' => $attributes['status'] ?? CaseStatus::UNDER_DIRECTOR_REVIEW,
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
                'claim_summary' => $this->richTextSanitizer->sanitize($attributes['claim_summary']),
                'amount' => $attributes['amount'] ?? null,
                'crime_scene' => $attributes['crime_scene'] ?? null,
                'police_station' => $attributes['police_station'] ?? null,
                'stolen_property_type' => $attributes['stolen_property_type'] ?? null,
                'stolen_property_estimated_value' => $attributes['stolen_property_estimated_value'] ?? null,
                'suspect_names' => $attributes['suspect_names'] ?? null,
                'statement_date' => $attributes['statement_date'] ?? null,
                'filing_date' => $attributes['filing_date'] ?? null,
                'next_hearing_date' => $attributes['next_hearing_date'] ?? null,
                'priority' => $attributes['priority'],
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($legalCase, $attributes['attachments'], $registrar);
            }

            return $legalCase->fresh();
        });
    }
}

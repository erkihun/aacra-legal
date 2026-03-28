<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OpenLegalCaseAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $sequenceNumberAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $registrar): LegalCase
    {
        return DB::transaction(function () use ($attributes, $registrar): LegalCase {
            $legalCase = LegalCase::query()->create([
                ...$attributes,
                'case_number' => $this->sequenceNumberAction->execute('CASE'),
                'registered_by_id' => $registrar->getKey(),
                'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($legalCase, $attributes['attachments'], $registrar);
            }

            return $legalCase->fresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class SubmitAdvisoryRequestAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $sequenceNumberAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $requester): AdvisoryRequest
    {
        return DB::transaction(function () use ($attributes, $requester): AdvisoryRequest {
            $request = AdvisoryRequest::query()->create([
                ...$attributes,
                'description' => $this->richTextSanitizer->sanitize($attributes['description'] ?? null),
                'request_number' => $this->sequenceNumberAction->execute('ADV'),
                'requester_user_id' => $requester->getKey(),
                'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
                'date_submitted' => now()->toDateString(),
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($request, $attributes['attachments'], $requester);
            }

            return $request->fresh([
                'department',
                'category',
                'requester',
                'attachments',
            ]);
        });
    }
}

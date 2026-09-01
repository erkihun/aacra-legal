<?php

declare(strict_types=1);

namespace App\Actions\Requester;

use App\Actions\GenerateSequenceNumberAction;
use App\Actions\StoreAttachmentAction;
use App\Enums\AdvisoryRequestStatus;
use App\Enums\DirectorDecision;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\RequesterAccount;
use App\Support\RequestLetterTemplateData;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class SubmitPortalAdvisoryRequestAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $sequenceNumberAction,
        private readonly ResolveDefaultRequesterLetterTemplateAction $resolveDefaultRequesterLetterTemplateAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RequestLetterTemplateData $requestLetterTemplateData,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, RequesterAccount $requester): AdvisoryRequest
    {
        return DB::transaction(function () use ($attributes, $requester): AdvisoryRequest {
            $template = $this->resolveDefaultRequesterLetterTemplateAction->execute();

            $request = AdvisoryRequest::query()->create([
                'request_number' => $this->sequenceNumberAction->execute('ADV'),
                'department_id' => $requester->department_id,
                'category_id' => $attributes['category_id'],
                'requester_account_id' => $requester->getKey(),
                'requester_user_id' => null,
                'letter_template_id' => $template->getKey(),
                'letter_snapshot' => $this->requestLetterTemplateData->snapshot($template),
                'subject' => $attributes['subject'],
                'description' => $this->richTextSanitizer->sanitize((string) ($attributes['description'] ?? '')),
                'request_type' => 'written',
                'priority' => 'medium',
                'status' => AdvisoryRequestStatus::SUBMITTED,
                'workflow_stage' => WorkflowStage::DIRECTOR,
                'director_decision' => DirectorDecision::PENDING,
                'date_submitted' => now()->toDateString(),
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($request, $attributes['attachments'], uploadedBy: null, requesterUploader: $requester);
            }

            return $request->fresh(['department', 'category', 'attachments']);
        });
    }
}

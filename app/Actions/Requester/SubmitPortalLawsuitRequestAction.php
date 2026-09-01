<?php

declare(strict_types=1);

namespace App\Actions\Requester;

use App\Actions\GenerateSequenceNumberAction;
use App\Actions\StoreAttachmentAction;
use App\Enums\LawsuitRequestStatus;
use App\Models\LawsuitFilingRequest;
use App\Models\RequesterAccount;
use App\Support\RequestLetterTemplateData;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class SubmitPortalLawsuitRequestAction
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
    public function execute(array $attributes, RequesterAccount $requester): LawsuitFilingRequest
    {
        return DB::transaction(function () use ($attributes, $requester): LawsuitFilingRequest {
            $template = $this->resolveDefaultRequesterLetterTemplateAction->execute();

            $request = LawsuitFilingRequest::query()->create([
                'request_code' => $this->sequenceNumberAction->execute('LSR'),
                'requesting_department_id' => $requester->department_id,
                'created_by' => null,
                'requester_account_id' => $requester->getKey(),
                'letter_template_id' => $template->getKey(),
                'letter_snapshot' => $this->requestLetterTemplateData->snapshot($template),
                'subject' => $attributes['subject'],
                'description' => $this->richTextSanitizer->sanitize((string) ($attributes['description'] ?? '')),
                'status' => LawsuitRequestStatus::SUBMITTED,
                'date_submitted' => now()->toDateString(),
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($request, $attributes['attachments'], uploadedBy: null, requesterUploader: $requester);
            }

            return $request->fresh(['requestingDepartment', 'attachments']);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LawsuitRequestStatus;
use App\Models\LawsuitFilingRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitLawsuitFilingRequestAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $sequenceNumberAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $requester): LawsuitFilingRequest
    {
        return DB::transaction(function () use ($attributes, $requester): LawsuitFilingRequest {
            $lawsuitRequest = LawsuitFilingRequest::query()->create([
                'requesting_department_id' => $attributes['requesting_department_id'],
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'request_code' => $this->sequenceNumberAction->execute('LSR'),
                'created_by' => $requester->getKey(),
                'status' => LawsuitRequestStatus::SUBMITTED,
                'date_submitted' => now()->toDateString(),
            ]);

            if (! empty($attributes['attachments'])) {
                $this->storeAttachmentAction->execute($lawsuitRequest, $attributes['attachments'], $requester);
            }

            return $lawsuitRequest->fresh(['requestingDepartment', 'creator', 'attachments']);
        });
    }
}

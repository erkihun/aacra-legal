<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AdvisoryResponse;
use App\Models\User;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;

class UpdateAdvisoryResponseAction
{
    public function __construct(
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(AdvisoryResponse $advisoryResponse, array $attributes, User $user, array $attachments = []): AdvisoryResponse
    {
        return DB::transaction(function () use ($advisoryResponse, $attributes, $user, $attachments): AdvisoryResponse {
            $advisoryResponse->update([
                'subject' => trim((string) ($attributes['subject'] ?? '')),
                'response' => $this->richTextSanitizer->sanitize($attributes['response'] ?? null),
                'summary' => trim((string) ($attributes['subject'] ?? '')),
                'advice_text' => $this->richTextSanitizer->sanitize($attributes['response'] ?? null),
            ]);

            if ($attachments !== []) {
                $this->storeAttachmentAction->execute($advisoryResponse, $attachments, $user);
            }

            $latestResponse = $advisoryResponse->advisoryRequest->responses()
                ->latest('responded_at')
                ->first();

            if ($latestResponse?->is($advisoryResponse)) {
                $advisoryResponse->advisoryRequest->update([
                    'internal_summary' => $advisoryResponse->subject,
                ]);
            }

            return $advisoryResponse->fresh(['responder', 'attachments.uploadedBy']);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LawsuitRequestStatus;
use App\Models\LawsuitFilingRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewLawsuitFilingRequestAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(LawsuitFilingRequest $lawsuitRequest, array $attributes, User $reviewer): LawsuitFilingRequest
    {
        if ($lawsuitRequest->status === LawsuitRequestStatus::APPROVED
            || $lawsuitRequest->status === LawsuitRequestStatus::REJECTED) {
            throw ValidationException::withMessages([
                'status' => __('The lawsuit filing request is not open for review.'),
            ]);
        }

        return DB::transaction(function () use ($lawsuitRequest, $attributes, $reviewer): LawsuitFilingRequest {
            $lawsuitRequest->update([
                'reviewed_by' => $reviewer->getKey(),
                'status' => LawsuitRequestStatus::from($attributes['status']),
                'reviewer_notes' => $attributes['reviewer_notes'] ?? null,
            ]);

            return $lawsuitRequest->fresh();
        });
    }
}

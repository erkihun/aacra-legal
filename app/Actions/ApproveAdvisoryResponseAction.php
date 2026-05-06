<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AdvisoryResponse;
use App\Models\User;
use App\Notifications\AdvisoryResponseRecordedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveAdvisoryResponseAction
{
    public function execute(AdvisoryResponse $advisoryResponse, User $approver): AdvisoryResponse
    {
        if (($advisoryResponse->approval_status ?? 'pending') === 'approved') {
            throw ValidationException::withMessages([
                'approval' => __('Advisory response already approved.'),
            ]);
        }

        return DB::transaction(function () use ($advisoryResponse, $approver): AdvisoryResponse {
            $advisoryResponse->update([
                'approval_status' => 'approved',
                'approved_by' => $approver->getKey(),
                'approved_at' => now(),
            ]);

            $advisoryResponse->loadMissing(['advisoryRequest.requester', 'responder', 'approver']);

            $requester = $advisoryResponse->advisoryRequest?->requester;

            if ($requester !== null) {
                DB::afterCommit(function () use ($requester, $advisoryResponse): void {
                    $requester->notify(new AdvisoryResponseRecordedNotification(
                        $advisoryResponse->advisoryRequest,
                        $advisoryResponse,
                    ));
                });
            }

            return $advisoryResponse->fresh(['advisoryRequest.requester', 'responder', 'approver']);
        });
    }
}

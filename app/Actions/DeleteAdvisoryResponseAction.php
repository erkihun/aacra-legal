<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAdvisoryResponseAction
{
    public function execute(AdvisoryResponse $advisoryResponse): void
    {
        DB::transaction(function () use ($advisoryResponse): void {
            $advisoryRequest = $advisoryResponse->advisoryRequest()->firstOrFail();

            $advisoryResponse->loadMissing('attachments');

            foreach ($advisoryResponse->attachments as $attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            }

            $advisoryResponse->delete();

            $latestResponse = $advisoryRequest->responses()
                ->latest('responded_at')
                ->first();

            if ($latestResponse !== null) {
                $advisoryRequest->update([
                    'internal_summary' => $latestResponse->subject ?? $latestResponse->summary,
                    'completed_at' => $latestResponse->responded_at,
                ]);

                return;
            }

            $advisoryRequest->update([
                'internal_summary' => null,
                'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
                'workflow_stage' => WorkflowStage::EXPERT,
                'completed_at' => null,
            ]);
        });
    }
}

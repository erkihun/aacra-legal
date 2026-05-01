<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApproveLetterAction
{
    public function execute(Letter $letter, User $approver): Letter
    {
        if (($letter->approval_status ?? 'draft') === 'approved') {
            throw ValidationException::withMessages([
                'approval' => __('letters.flash.already_approved'),
            ]);
        }

        if (! filled($approver->name)) {
            throw ValidationException::withMessages([
                'approval' => __('letters.approval_errors.missing_approver_name'),
            ]);
        }

        if (! filled($approver->signature_path)) {
            throw ValidationException::withMessages([
                'approval' => __('letters.approval_errors.missing_signature'),
            ]);
        }

        return DB::transaction(function () use ($letter, $approver): Letter {
            $letter->forceFill([
                'approval_status' => 'approved',
                'approved_by' => $approver->getKey(),
                'approved_at' => now(),
                'approved_signature_path_snapshot' => $this->snapshotAsset($approver->signature_path, (string) $letter->getKey(), 'approved-signature'),
                'approved_signer_name_snapshot' => $approver->name,
                'approved_signer_title_snapshot' => filled($approver->job_title) ? $approver->job_title : null,
                'status' => 'final',
                'updated_by' => $approver->getKey(),
            ])->save();

            return $letter->refresh()->loadMissing(['template', 'creator', 'updater', 'approver']);
        });
    }

    private function snapshotAsset(?string $sourcePath, string $letterId, string $prefix): ?string
    {
        if (! is_string($sourcePath) || trim($sourcePath) === '') {
            return null;
        }

        $normalizedPath = ltrim(trim($sourcePath), '/');

        if (
            ! str_starts_with($normalizedPath, 'letter-templates/')
            && ! str_starts_with($normalizedPath, 'users/')
            && ! str_starts_with($normalizedPath, 'letters/')
        ) {
            return $normalizedPath;
        }

        if (! Storage::disk('public')->exists($normalizedPath)) {
            return $normalizedPath;
        }

        $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION) ?: 'png';
        $copiedPath = "letters/{$letterId}/{$prefix}-".Str::lower((string) Str::uuid()).".{$extension}";
        Storage::disk('public')->copy($normalizedPath, $copiedPath);

        return $copiedPath;
    }
}

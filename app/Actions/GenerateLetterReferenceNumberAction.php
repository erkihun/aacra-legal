<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LetterTemplate;
use Illuminate\Support\Facades\DB;

class GenerateLetterReferenceNumberAction
{
    public function execute(LetterTemplate $letterTemplate): string
    {
        return DB::transaction(function () use ($letterTemplate): string {
            /** @var LetterTemplate $lockedTemplate */
            $lockedTemplate = LetterTemplate::query()
                ->whereKey($letterTemplate->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextNumber = max(
                (int) $lockedTemplate->current_reference_number + 1,
                (int) $lockedTemplate->reference_start_number,
            );

            $lockedTemplate->forceFill([
                'current_reference_number' => $nextNumber,
            ])->save();

            return $this->formatReferenceNumber($lockedTemplate, $nextNumber);
        });
    }

    public function preview(LetterTemplate $letterTemplate): string
    {
        $nextNumber = max(
            (int) $letterTemplate->current_reference_number + 1,
            (int) $letterTemplate->reference_start_number,
        );

        return $this->formatReferenceNumber($letterTemplate, $nextNumber);
    }

    private function formatReferenceNumber(LetterTemplate $letterTemplate, int $sequence): string
    {
        $config = is_array($letterTemplate->numbering_config) ? $letterTemplate->numbering_config : [];
        $separator = (string) ($config['separator'] ?? '/');
        $includeYear = (bool) ($config['include_year'] ?? true);
        $padLength = max(1, (int) ($config['pad_length'] ?? 4));
        $prefix = trim((string) ($letterTemplate->reference_prefix ?? $letterTemplate->reference_label ?? $letterTemplate->code));
        $number = str_pad((string) $sequence, $padLength, '0', STR_PAD_LEFT);

        $parts = array_values(array_filter([
            $prefix,
            $includeYear ? (string) now()->year : null,
            $number,
        ], static fn (?string $value): bool => $value !== null && $value !== ''));

        return implode($separator, $parts);
    }
}

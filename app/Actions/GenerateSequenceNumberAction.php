<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AdvisoryRequest;
use App\Models\Complaint;
use App\Models\LegalCase;
use App\Models\SequenceCounter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class GenerateSequenceNumberAction
{
    public function execute(string $scope, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $nextValue = DB::transaction(function () use ($scope, $year): int {
            $counter = SequenceCounter::query()
                ->where('scope', $scope)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $nextValue = $this->nextValueFromExistingRecords($scope, $year);

                try {
                    SequenceCounter::query()->create([
                        'scope' => $scope,
                        'year' => $year,
                        'next_value' => $nextValue + 1,
                    ]);

                    return $nextValue;
                } catch (UniqueConstraintViolationException) {
                    $counter = SequenceCounter::query()
                        ->where('scope', $scope)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $value = $counter->next_value;
            $counter->update(['next_value' => $value + 1]);

            return $value;
        });

        return sprintf('%s-%d-%04d', strtoupper($scope), $year, $nextValue);
    }

    private function nextValueFromExistingRecords(string $scope, int $year): int
    {
        $prefix = sprintf('%s-%d-', strtoupper($scope), $year);
        $existingNumber = match (strtoupper($scope)) {
            'ADV' => AdvisoryRequest::query()
                ->where('request_number', 'like', "{$prefix}%")
                ->orderByDesc('request_number')
                ->value('request_number'),
            'CASE' => LegalCase::query()
                ->where('case_number', 'like', "{$prefix}%")
                ->orderByDesc('case_number')
                ->value('case_number'),
            'CMP' => Complaint::query()
                ->where('complaint_number', 'like', "{$prefix}%")
                ->orderByDesc('complaint_number')
                ->value('complaint_number'),
            default => null,
        };

        if ($existingNumber === null && ! in_array(strtoupper($scope), ['ADV', 'CASE'], true)) {
            $existingNumber = Complaint::query()
                ->where('complaint_number', 'like', "{$prefix}%")
                ->orderByDesc('complaint_number')
                ->value('complaint_number');
        }

        if (! is_string($existingNumber) || ! str_starts_with($existingNumber, $prefix)) {
            return 1;
        }

        $sequence = (int) substr($existingNumber, strlen($prefix));

        return max(1, $sequence + 1);
    }
}

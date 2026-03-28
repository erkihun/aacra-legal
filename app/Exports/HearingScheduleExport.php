<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HearingScheduleExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn (array $row) => [
            $row['case_number'],
            $row['plaintiff'],
            $row['court'],
            $row['assigned_expert'],
            $row['next_hearing_date'],
            $row['status'],
        ]);
    }

    public function headings(): array
    {
        return ['Case Number', 'Plaintiff', 'Court', 'Assigned Expert', 'Next Hearing Date', 'Status'];
    }
}

<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TurnaroundTimesExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn (array $row) => [
            $row['module'],
            $row['reference'],
            $row['subject'],
            $row['opened_at'],
            $row['completed_at'],
            $row['turnaround_days'],
        ]);
    }

    public function headings(): array
    {
        return ['Module', 'Reference', 'Subject', 'Opened At', 'Completed At', 'Turnaround Days'];
    }
}

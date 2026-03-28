<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpertWorkloadExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn (array $row) => [
            $row['expert'],
            $row['advisory'],
            $row['cases'],
            $row['total'],
        ]);
    }

    public function headings(): array
    {
        return ['Expert', 'Active Advisory', 'Active Cases', 'Total Active Work'];
    }
}

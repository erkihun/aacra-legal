<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CasesByStatusExport implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(fn (array $row) => [
            $row['status'],
            $row['total'],
        ]);
    }

    public function headings(): array
    {
        return ['Status', 'Total Cases'];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranchId = Branch::query()
            ->where('is_head_office', true)
            ->value('id')
            ?? Branch::query()->orderBy('created_at')->value('id');

        foreach ($this->departments() as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                [...$department, 'branch_id' => $defaultBranchId, 'is_active' => true],
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function departments(): array
    {
        return [
            ['code' => 'EXEC', 'name_en' => 'Executive Office', 'name_am' => 'የአስፈፃሚ ቢሮ'],
            ['code' => 'FIN', 'name_en' => 'Finance Department', 'name_am' => 'የፋይናንስ መምሪያ'],
            ['code' => 'HR', 'name_en' => 'Human Resources', 'name_am' => 'የሰው ኃይል መምሪያ'],
            ['code' => 'PROC', 'name_en' => 'Procurement Department', 'name_am' => 'የግዥ መምሪያ'],
            ['code' => 'ICT', 'name_en' => 'ICT Department', 'name_am' => 'የአይሲቲ መምሪያ'],
            ['code' => 'LEG', 'name_en' => 'Legal Department', 'name_am' => 'የሕግ መምሪያ'],
        ];
    }
}

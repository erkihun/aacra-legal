<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdvisoryCategory;
use Illuminate\Database\Seeder;

class AdvisoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $category) {
            AdvisoryCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                [...$category, 'is_active' => true],
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function categories(): array
    {
        return [
            ['code' => 'HR-POL', 'name_en' => 'HR Policy', 'name_am' => 'የሰው ኃይል ፖሊሲ'],
            ['code' => 'PROC-CON', 'name_en' => 'Procurement Contract', 'name_am' => 'የግዥ ውል'],
            ['code' => 'COMP', 'name_en' => 'Compliance', 'name_am' => 'ተገዢነት'],
            ['code' => 'EMP', 'name_en' => 'Employment Matter', 'name_am' => 'የሥራ ግንኙነት ጉዳይ'],
        ];
    }
}

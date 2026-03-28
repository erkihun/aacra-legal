<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LegalCaseType;
use Illuminate\Database\Seeder;

class LegalCaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->types() as $type) {
            LegalCaseType::query()->updateOrCreate(
                ['code' => $type['code']],
                [...$type, 'is_active' => true],
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function types(): array
    {
        return [
            ['code' => 'LAB', 'name_en' => 'Labor Dispute', 'name_am' => 'የሥራ ክርክር'],
            ['code' => 'CIV', 'name_en' => 'Civil Claim', 'name_am' => 'የፍትሐ ብሔር ክስ'],
            ['code' => 'ADM', 'name_en' => 'Administrative Case', 'name_am' => 'የአስተዳደር ጉዳይ'],
            ['code' => 'TAX', 'name_en' => 'Tax Appeal', 'name_am' => 'የታክስ አቤቱታ'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->courts() as $court) {
            Court::query()->updateOrCreate(
                ['code' => $court['code']],
                [...$court, 'is_active' => true],
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function courts(): array
    {
        return [
            ['code' => 'AA-FHC', 'name_en' => 'Addis Ababa Federal High Court', 'name_am' => 'አዲስ አበባ ፌዴራል ከፍተኛ ፍርድ ቤት', 'level' => 'Federal High Court', 'city' => 'Addis Ababa'],
            ['code' => 'AA-FSC', 'name_en' => 'Addis Ababa Federal Supreme Court', 'name_am' => 'አዲስ አበባ ፌዴራል ጠቅላይ ፍርድ ቤት', 'level' => 'Federal Supreme Court', 'city' => 'Addis Ababa'],
            ['code' => 'AA-LAB', 'name_en' => 'Addis Ababa Labor Court', 'name_am' => 'አዲስ አበባ የሥራ ፍርድ ቤት', 'level' => 'Labor Court', 'city' => 'Addis Ababa'],
        ];
    }
}

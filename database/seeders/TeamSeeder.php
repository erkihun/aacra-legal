<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TeamType;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->teams() as $team) {
            Team::query()->updateOrCreate(
                ['code' => $team['code']],
                [
                    'name_en' => $team['name_en'],
                    'name_am' => $team['name_am'],
                    'type' => $team['type'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function teams(): array
    {
        return [
            ['code' => 'LIT', 'name_en' => 'Court Case Follow-Up Team', 'name_am' => 'የፍርድ ቤት ጉዳይ ክትትል ቡድን', 'type' => TeamType::LITIGATION],
            ['code' => 'ADV', 'name_en' => 'Legal Advisory Team', 'name_am' => 'የሕግ ምክር ቡድን', 'type' => TeamType::ADVISORY],
            ['code' => 'ADM', 'name_en' => 'Legal Administration', 'name_am' => 'የሕግ አስተዳደር', 'type' => TeamType::ADMINISTRATION],
        ];
    }
}

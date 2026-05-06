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
                    'supports_advisory' => $team['supports_advisory'],
                    'supports_court_case' => $team['supports_court_case'],
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
            ['code' => 'LIT', 'name_en' => 'Court Case Follow-Up Team', 'name_am' => 'የፍርድ ቤት ጉዳይ ክትትል ቡድን', 'type' => TeamType::LITIGATION, 'supports_advisory' => false, 'supports_court_case' => true],
            ['code' => 'ADV', 'name_en' => 'Legal Advisory Team', 'name_am' => 'የሕግ ምክር ቡድን', 'type' => TeamType::ADVISORY, 'supports_advisory' => true, 'supports_court_case' => false],
            ['code' => 'ADM', 'name_en' => 'Legal Administration', 'name_am' => 'የሕግ አስተዳደር', 'type' => TeamType::ADMINISTRATION, 'supports_advisory' => false, 'supports_court_case' => false],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            TeamSeeder::class,
            AdvisoryCategorySeeder::class,
            CourtSeeder::class,
            LegalCaseTypeSeeder::class,
        ]);
    }
}

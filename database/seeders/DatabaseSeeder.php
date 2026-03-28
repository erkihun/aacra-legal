<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DepartmentSeeder::class,
            TeamSeeder::class,
            AdvisoryCategorySeeder::class,
            CourtSeeder::class,
            LegalCaseTypeSeeder::class,
            DemoUserSeeder::class,
            PublicPostSeeder::class,
            DemoWorkflowSeeder::class,
        ]);
    }
}

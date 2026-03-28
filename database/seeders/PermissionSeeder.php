<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return RolesAndPermissionsSeeder::permissions();
    }

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}

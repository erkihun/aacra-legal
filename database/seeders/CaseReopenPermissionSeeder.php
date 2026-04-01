<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CaseReopenPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('cases.reopen', 'web');
        Permission::findOrCreate('case-reopen', 'web');

        $superAdmin = Role::findByName(SystemRole::SUPER_ADMIN->value, 'web');
        $superAdmin->givePermissionTo(['cases.reopen', 'case-reopen']);
    }
}

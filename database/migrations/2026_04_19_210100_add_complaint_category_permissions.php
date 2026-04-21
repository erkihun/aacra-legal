<?php

declare(strict_types=1);

use App\Enums\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('complaint-categories.view', 'web');
        Permission::findOrCreate('complaint-categories.manage', 'web');

        Role::findOrCreate(SystemRole::SUPER_ADMIN->value, 'web')
            ->givePermissionTo(['complaint-categories.view', 'complaint-categories.manage']);

        Role::findOrCreate(SystemRole::LEGAL_DIRECTOR->value, 'web')
            ->givePermissionTo(['complaint-categories.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([SystemRole::SUPER_ADMIN->value, SystemRole::LEGAL_DIRECTOR->value] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->revokePermissionTo(['complaint-categories.view', 'complaint-categories.manage']);
        }

        Permission::query()
            ->whereIn('name', ['complaint-categories.view', 'complaint-categories.manage'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

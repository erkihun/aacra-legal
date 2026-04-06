<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncUserManagementPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync-user-management';

    protected $description = 'Synchronize user management permissions without running broad seeders.';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $managedPermissions = ['users.ban', 'users.delete'];

        foreach ($managedPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (RolesAndPermissionsSeeder::rolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $desiredPermissions = array_values(array_intersect($managedPermissions, $permissions));
            $currentManagedPermissions = $role->permissions()
                ->whereIn('name', $managedPermissions)
                ->pluck('name')
                ->all();

            foreach ($managedPermissions as $permissionName) {
                $hasPermission = in_array($permissionName, $currentManagedPermissions, true);
                $shouldHavePermission = in_array($permissionName, $desiredPermissions, true);

                if ($shouldHavePermission && ! $hasPermission) {
                    $role->givePermissionTo($permissionName);
                }

                if (! $shouldHavePermission && $hasPermission) {
                    $role->revokePermissionTo($permissionName);
                }
            }

            $this->line(
                $desiredPermissions === []
                    ? "Revoked managed user permissions from {$roleName}."
                    : "Synced managed user permissions for {$roleName}: ".implode(', ', $desiredPermissions),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('User management permissions synchronized.');

        return self::SUCCESS;
    }
}

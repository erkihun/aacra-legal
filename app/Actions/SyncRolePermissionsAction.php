<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncRolePermissionsAction
{
    public function execute(Role $role, array $permissions, User $actor): Role
    {
        $previousPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions);
        $role->load('permissions', 'users');

        activity()
            ->causedBy($actor)
            ->event('permissions_synced')
            ->withProperties([
                'role' => $role->name,
                'previous_permissions' => $previousPermissions,
                'current_permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
            ])
            ->log('Role permissions updated.');

        return $role;
    }
}

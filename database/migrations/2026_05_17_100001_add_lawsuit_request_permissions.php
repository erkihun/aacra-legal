<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'lawsuit-requests.view',
            'lawsuit-requests.create',
            'lawsuit-requests.update',
            'lawsuit-requests.review',
            'lawsuit-requests.approve',
            'lawsuit-requests.reject',
            'lawsuit-requests.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'lawsuit-requests.view',
                'lawsuit-requests.create',
                'lawsuit-requests.update',
                'lawsuit-requests.review',
                'lawsuit-requests.approve',
                'lawsuit-requests.reject',
                'lawsuit-requests.delete',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

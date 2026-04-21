<?php

declare(strict_types=1);

use App\Enums\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'branches.view',
        'branches.create',
        'branches.update',
        'branches.delete',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(SystemRole::SUPER_ADMIN->value, 'web');
        $superAdmin->givePermissionTo($this->permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate(SystemRole::SUPER_ADMIN->value, 'web');
        $superAdmin->revokePermissionTo($this->permissions);

        Permission::query()
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

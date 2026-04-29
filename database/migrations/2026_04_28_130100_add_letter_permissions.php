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
        'letters.view',
        'letters.create',
        'letters.update',
        'letters.delete',
        'letters.preview',
        'letters.print',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(SystemRole::SUPER_ADMIN->value, 'web')
            ->givePermissionTo($this->permissions);

        Role::findOrCreate(SystemRole::LEGAL_DIRECTOR->value, 'web')
            ->givePermissionTo([
                'letters.view',
                'letters.create',
                'letters.update',
                'letters.preview',
                'letters.print',
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([SystemRole::SUPER_ADMIN->value, SystemRole::LEGAL_DIRECTOR->value] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->revokePermissionTo($this->permissions);
        }

        Permission::query()
            ->whereIn('name', $this->permissions)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

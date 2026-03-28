<?php

declare(strict_types=1);

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('allows a super admin to manage non-protected role permissions', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail();

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles'));

    $this->actingAs($admin)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Roles/Edit')
            ->where('roleItem.name', SystemRole::LEGAL_DIRECTOR->value)
            ->has('permissionGroups'));

    $this->actingAs($admin)
        ->patch(route('roles.update', $role), [
            'permissions' => [
                'dashboard.view',
                'reports.view',
                'audit-logs.view',
            ],
        ])
        ->assertRedirect(route('roles.edit', $role));

    expect($role->fresh()?->permissions->pluck('name')->sort()->values()->all())
        ->toBe([
            'audit-logs.view',
            'dashboard.view',
            'reports.view',
        ]);
});

it('protects the super admin role from permission changes', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::SUPER_ADMIN->value)->firstOrFail();
    $originalPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

    $this->actingAs($admin)
        ->patch(route('roles.update', $role), [
            'permissions' => ['dashboard.view'],
        ])
        ->assertSessionHas('error', __('The Super Admin role permissions are managed by the system.'));

    expect($role->fresh()?->permissions->pluck('name')->sort()->values()->all())
        ->toBe($originalPermissions);
});

it('forbids non-authorized users from role management', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail();

    $this->actingAs($director)
        ->get(route('roles.index'))
        ->assertForbidden();

    $this->actingAs($director)
        ->patch(route('roles.update', $role), [
            'permissions' => ['dashboard.view'],
        ])
        ->assertForbidden();
});

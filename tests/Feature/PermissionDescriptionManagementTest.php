<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('stores english and amharic descriptions for every permission', function (): void {
    $expected = Database\Seeders\RolesAndPermissionsSeeder::permissions();

    expect(Permission::query()->count())->toBe(count($expected));

    Permission::query()->orderBy('name')->get()->each(function (Permission $permission): void {
        expect($permission->description_en)->toBeString()->not->toBe('')
            ->and($permission->description_am)->toBeString()->not->toBe('')
            ->and((bool) preg_match('/[\x{1200}-\x{137F}]/u', (string) $permission->description_am))->toBeTrue();
    });
});

it('shows bilingual descriptions on the permission list page', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('permissions.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Permissions/Index')
            ->has('permissions', Permission::query()->count())
            ->where('permissions.0.description_en', Permission::query()->orderBy('name')->firstOrFail()->description_en)
            ->where('permissions.0.description_am', Permission::query()->orderBy('name')->firstOrFail()->description_am)
        );
});

it('shows bilingual permission descriptions on the role assignment pages', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', 'Legal Director')->firstOrFail();
    $dashboardPermission = Permission::query()->where('name', 'dashboard.view')->firstOrFail();

    $assertPage = fn (AssertableInertia $page) => $page
        ->has('permissionGroups')
        ->where('permissionGroups.0.items.0.description_en', Permission::query()->orderBy('name')->firstOrFail()->description_en)
        ->where('permissionGroups.0.items.0.description_am', Permission::query()->orderBy('name')->firstOrFail()->description_am);

    $this->actingAs($admin)
        ->get(route('roles.create'))
        ->assertOk()
        ->assertInertia($assertPage);

    $this->actingAs($admin)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $assertPage($page)
            ->where(
                'permissionGroups',
                fn ($groups) => collect($groups)
                    ->flatMap(fn (array $group) => $group['items'])
                    ->contains(fn (array $item) => $item['name'] === 'dashboard.view'
                        && $item['description_en'] === $dashboardPermission->description_en
                        && $item['description_am'] === $dashboardPermission->description_am)
            )
        );
});

it('updates permission descriptions without changing permission keys or role assignments', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $permission = Permission::query()->where('name', 'dashboard.view')->firstOrFail();
    $role = Role::query()->where('name', 'Legal Director')->firstOrFail();
    $rolePermissionsBefore = $role->permissions()->pluck('name')->sort()->values()->all();

    $this->actingAs($admin)
        ->patch(route('permissions.update', $permission), [
            'description_en' => 'Allows the user to open the dashboard and review summary indicators.',
            'description_am' => 'ተጠቃሚው ዳሽቦርዱን እንዲከፍት እና የማጠቃለያ መለኪያዎችን እንዲመለከት ያስችለዋል።',
        ])
        ->assertRedirect(route('permissions.edit', $permission))
        ->assertSessionHas('success', __('permissions.updated_success'));

    $permission->refresh();
    $role->refresh();

    expect($permission->name)->toBe('dashboard.view')
        ->and($permission->description_en)->toBe('Allows the user to open the dashboard and review summary indicators.')
        ->and($permission->description_am)->toBe('ተጠቃሚው ዳሽቦርዱን እንዲከፍት እና የማጠቃለያ መለኪያዎችን እንዲመለከት ያስችለዋል።')
        ->and($role->permissions()->pluck('name')->sort()->values()->all())->toEqual($rolePermissionsBefore);
});

it('keeps permission management protected from unauthorized users', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $permission = Permission::query()->where('name', 'dashboard.view')->firstOrFail();

    $this->actingAs($requester)->get(route('permissions.index'))->assertForbidden();
    $this->actingAs($requester)->get(route('permissions.edit', $permission))->assertForbidden();
});

<?php

declare(strict_types=1);

use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('allows a super admin to create, update, and delete users', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $department = Department::query()->firstOrFail();
    $team = Team::query()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data')
            ->where('can.create', true)
            ->where('can.update', true));

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'employee_number' => 'EMP-9901',
            'name' => 'Admin Module User',
            'email' => 'admin.module.user@ldms.test',
            'phone' => '+251911000001',
            'job_title' => 'Legal Officer',
            'locale' => LocaleCode::ENGLISH->value,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'role_name' => SystemRole::LEGAL_EXPERT->value,
            'is_active' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'admin.module.user@ldms.test')->firstOrFail();

    expect($user->department_id)->toBe($department->id)
        ->and($user->team_id)->toBe($team->id)
        ->and($user->locale)->toBe(LocaleCode::ENGLISH)
        ->and($user->hasRole(SystemRole::LEGAL_EXPERT->value))->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'employee_number' => 'EMP-9901',
            'name' => 'Admin Module User Updated',
            'email' => 'admin.module.user@ldms.test',
            'phone' => '+251911000002',
            'job_title' => 'Senior Legal Officer',
            'locale' => LocaleCode::AMHARIC->value,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'role_name' => SystemRole::REGISTRAR->value,
            'is_active' => false,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.show', $user));

    expect($user->fresh()?->name)->toBe('Admin Module User Updated');
    expect($user->fresh()?->phone)->toBe('+251911000002');
    expect($user->fresh()?->job_title)->toBe('Senior Legal Officer');
    expect($user->fresh()?->locale)->toBe(LocaleCode::AMHARIC);
    expect($user->fresh()?->is_active)->toBeFalse();
    expect($user->fresh()->hasRole(SystemRole::REGISTRAR->value))->toBeTrue();

    auth()->logout();
    $this->assertGuest();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
        'email' => 'admin.module.user@ldms.test',
    ]);
});

it('prevents deleting your own account and deactivating the last super admin', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertSessionHas('error', __('You cannot delete your own user account from user management.'));

    $this->actingAs($admin)
        ->from(route('users.edit', $admin))
        ->patch(route('users.update', $admin), [
            'employee_number' => $admin->employee_number,
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'job_title' => $admin->job_title,
            'locale' => $admin->locale?->value ?? LocaleCode::ENGLISH->value,
            'department_id' => $admin->department_id,
            'team_id' => $admin->team_id,
            'role_name' => SystemRole::SUPER_ADMIN->value,
            'is_active' => false,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrors('is_active');

    expect($admin->fresh()?->is_active)->toBeTrue();
});

it('blocks unauthorized access to user management routes', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($requester)
        ->get(route('users.create'))
        ->assertForbidden();
});

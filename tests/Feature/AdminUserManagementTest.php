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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');

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
            'national_id' => '1234 5678 9012 3456',
            'telegram_username' => '@admin_module_user',
            'locale' => LocaleCode::ENGLISH->value,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'role_name' => SystemRole::LEGAL_EXPERT->value,
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('avatar.png', 120, 120),
            'signature' => UploadedFile::fake()->image('signature.png', 240, 120),
            'stamp' => UploadedFile::fake()->image('stamp.png', 160, 160),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $user = User::query()->where('email', 'admin.module.user@ldms.test')->firstOrFail();

    expect($user->department_id)->toBe($department->id)
        ->and($user->team_id)->toBe($team->id)
        ->and($user->locale)->toBe(LocaleCode::ENGLISH)
        ->and($user->hasRole(SystemRole::LEGAL_EXPERT->value))->toBeTrue()
        ->and($user->national_id)->toBe('1234567890123456')
        ->and($user->telegram_username)->toBe('@admin_module_user')
        ->and($user->avatar_path)->not()->toBeNull()
        ->and($user->signature_path)->not()->toBeNull()
        ->and($user->stamp_path)->not()->toBeNull();

    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('public')->assertExists($user->signature_path);
    Storage::disk('public')->assertExists($user->stamp_path);

    $originalAvatarPath = $user->avatar_path;
    $originalSignaturePath = $user->signature_path;
    $originalStampPath = $user->stamp_path;

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'employee_number' => 'EMP-9901',
            'name' => 'Admin Module User Updated',
            'email' => 'admin.module.user@ldms.test',
            'phone' => '+251911000002',
            'job_title' => 'Senior Legal Officer',
            'national_id' => '4321 8765 2109 6543',
            'telegram_username' => '@updated_user123',
            'locale' => LocaleCode::AMHARIC->value,
            'department_id' => $department->id,
            'team_id' => $team->id,
            'role_name' => SystemRole::REGISTRAR->value,
            'is_active' => false,
            'avatar' => UploadedFile::fake()->image('avatar-updated.png', 120, 120),
            'signature' => UploadedFile::fake()->image('signature-updated.png', 240, 120),
            'stamp' => UploadedFile::fake()->image('stamp-updated.png', 160, 160),
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.show', $user));

    expect($user->fresh()?->name)->toBe('Admin Module User Updated');
    expect($user->fresh()?->phone)->toBe('+251911000002');
    expect($user->fresh()?->job_title)->toBe('Senior Legal Officer');
    expect($user->fresh()?->national_id)->toBe('4321876521096543');
    expect($user->fresh()?->telegram_username)->toBe('@updated_user123');
    expect($user->fresh()?->locale)->toBe(LocaleCode::AMHARIC);
    expect($user->fresh()?->is_active)->toBeFalse();
    expect($user->fresh()->hasRole(SystemRole::REGISTRAR->value))->toBeTrue();
    expect($user->fresh()?->avatar_path)->not()->toBe($originalAvatarPath);
    expect($user->fresh()?->signature_path)->not()->toBe($originalSignaturePath);
    expect($user->fresh()?->stamp_path)->not()->toBe($originalStampPath);

    Storage::disk('public')->assertMissing($originalAvatarPath);
    Storage::disk('public')->assertMissing($originalSignaturePath);
    Storage::disk('public')->assertMissing($originalStampPath);
    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    Storage::disk('public')->assertExists($user->fresh()->signature_path);
    Storage::disk('public')->assertExists($user->fresh()->stamp_path);

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Edit')
            ->where('userItem.national_id', '4321 8765 2109 6543')
            ->where('userItem.telegram_username', '@updated_user123')
            ->where('userItem.avatar_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/'))
            ->where('userItem.signature_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/'))
            ->where('userItem.stamp_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/')));

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

it('lists all users with stable pagination and preserves applied filters', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $department = Department::query()->firstOrFail();
    $team = Team::query()->firstOrFail();

    foreach (range(1, 15) as $index) {
        $user = User::factory()->create([
            'department_id' => $department->id,
            'team_id' => $team->id,
            'name' => sprintf('Paged User %02d', $index),
            'email' => sprintf('paged-user-%02d@ldms.test', $index),
            'phone' => sprintf('+251955000%03d', $index),
        ]);

        $user->assignRole(SystemRole::LEGAL_EXPERT->value);
    }

    $totalUsers = User::count();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 12)
            ->where('users.current_page', 1)
            ->where('users.last_page', 2)
            ->where('users.per_page', 12)
            ->where('users.total', $totalUsers));

    $this->actingAs($admin)
        ->get(route('users.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', $totalUsers - 12)
            ->where('users.current_page', 2)
            ->where('users.last_page', 2)
            ->where('users.total', $totalUsers)
            ->where('users.data', fn ($rows) => collect($rows)->contains(
                fn (array $row) => $row['email'] === 'paged-user-15@ldms.test'
            )));

    $this->actingAs($admin)
        ->get(route('users.index', [
            'search' => '+251955000013',
            'department_id' => $department->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Index')
            ->where('filters.search', '+251955000013')
            ->where('filters.department_id', $department->id)
            ->where('users.total', 1)
            ->has('users.data', 1)
            ->where('users.data.0.email', 'paged-user-13@ldms.test'));

    $this->actingAs($admin)
        ->get(route('users.index', [
            'search' => 'Paged User',
            'department_id' => $department->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Users/Index')
            ->where('users.current_page', 1)
            ->where('users.last_page', 2)
            ->where('users.next_page_url', fn (?string $url) => is_string($url)
                && str_contains(urldecode($url), 'search=Paged User')
                && str_contains($url, 'department_id='.$department->id)
                && str_contains($url, 'page=2')));
});

it('validates national id and telegram username on user create and update', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $department = Department::query()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'employee_number' => 'EMP-9902',
            'name' => 'Invalid Metadata User',
            'email' => 'invalid.metadata.user@ldms.test',
            'phone' => '+251911000099',
            'job_title' => 'Legal Officer',
            'national_id' => '1234 5678',
            'telegram_username' => 'john_doe',
            'locale' => LocaleCode::ENGLISH->value,
            'department_id' => $department->id,
            'team_id' => null,
            'role_name' => SystemRole::LEGAL_EXPERT->value,
            'is_active' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['national_id', 'telegram_username']);

    $user = User::factory()->create([
        'department_id' => $department->id,
        'email' => 'valid.update.target@ldms.test',
    ]);
    $user->assignRole(SystemRole::LEGAL_EXPERT->value);

    $this->actingAs($admin)
        ->patch(route('users.update', $user), [
            'employee_number' => $user->employee_number,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'national_id' => 'abcd efgh ijkl mnop',
            'telegram_username' => '@bad-name!',
            'locale' => $user->locale?->value ?? LocaleCode::ENGLISH->value,
            'department_id' => $user->department_id,
            'team_id' => $user->team_id,
            'role_name' => SystemRole::LEGAL_EXPERT->value,
            'is_active' => true,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrors(['national_id', 'telegram_username']);
});

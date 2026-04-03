<?php

declare(strict_types=1);

use App\Enums\LocaleCode;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    Storage::fake('public');
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Profile/Edit')
            ->where('auth.user.avatar_url', null)
            ->where('auth.user.national_id', null)
            ->where('auth.user.telegram_username', null));
});

test('profile information and media can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+251911123456',
            'national_id' => '1234 5678 9012 3456',
            'telegram_username' => '@test_user123',
            'locale' => LocaleCode::AMHARIC->value,
            'avatar' => UploadedFile::fake()->image('avatar.png', 120, 120),
            'signature' => UploadedFile::fake()->image('signature.png', 240, 120),
            'stamp' => UploadedFile::fake()->image('stamp.png', 120, 120),
        ], [
            'Content-Type' => 'multipart/form-data',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('+251911123456', $user->phone);
    $this->assertSame('1234567890123456', $user->national_id);
    $this->assertSame('@test_user123', $user->telegram_username);
    $this->assertSame(LocaleCode::AMHARIC, $user->locale);
    $this->assertNull($user->email_verified_at);
    $this->assertSame(LocaleCode::AMHARIC->value, session('locale'));
    $this->assertNotNull($user->avatar_path);
    $this->assertNotNull($user->signature_path);
    $this->assertNotNull($user->stamp_path);

    Storage::disk('public')->assertExists($user->avatar_path);
    Storage::disk('public')->assertExists($user->signature_path);
    Storage::disk('public')->assertExists($user->stamp_path);

    $this->actingAs($user->fresh())
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.avatar_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/'))
            ->where('auth.user.signature_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/'))
            ->where('auth.user.stamp_url', fn (?string $value) => is_string($value) && str_contains($value, '/branding-assets/users/'))
            ->where('auth.user.national_id', '1234 5678 9012 3456')
            ->where('auth.user.telegram_username', '@test_user123'));
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'locale' => LocaleCode::ENGLISH->value,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user cannot delete their own account from the profile route', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile');

    $response
        ->assertRedirect('/profile')
        ->assertSessionHas('error', __('profile.delete_disabled'));

    $this->assertAuthenticatedAs($user);
    $this->assertNotNull($user->fresh());
});

test('profile page no longer includes the delete account section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk();

    expect(file_get_contents(resource_path('js/Pages/Profile/Edit.tsx')))
        ->not->toContain('DeleteUserForm')
        ->not->toContain("profile.delete_title")
        ->not->toContain("profile.delete_account");
});

test('profile update validates national id and telegram username', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => LocaleCode::ENGLISH->value,
            'national_id' => '1234 5678',
            'telegram_username' => 'bad-name',
        ])
        ->assertSessionHasErrors(['national_id', 'telegram_username'])
        ->assertRedirect('/profile');
});

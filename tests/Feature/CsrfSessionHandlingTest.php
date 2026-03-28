<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('shares the csrf token in guest inertia responses and blade meta output', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('meta name="csrf-token"', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('csrf_token', csrf_token()));
});

it('redirects back gracefully with a flash error when the csrf token is invalid', function (): void {
    $this->withMiddleware(ValidateCsrfToken::class);

    $user = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            '_token' => 'invalid-token',
            'name' => 'Updated Admin',
            'email' => 'admin@ldms.test',
            'phone' => '+251911000000',
            'locale' => 'en',
        ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->name)->toBe($user->name);
});

it('canonicalizes loopback hosts to a single local origin to prevent split session cookies', function (): void {
    config(['app.url' => 'http://127.0.0.1:8000']);

    $response = $this->call('GET', '/login', [], [], [], [
        'HTTP_HOST' => 'localhost:8000',
        'SERVER_PORT' => 8000,
        'REQUEST_SCHEME' => 'http',
        'HTTPS' => 'off',
    ]);

    $response->assertRedirect('http://127.0.0.1:8000/login');
});

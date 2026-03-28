<?php

use App\Models\Department;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;

test('registration screen can be rendered', function () {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);

    $department = Department::query()->where('code', 'HR')->firstOrFail();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'department_id' => $department->id,
        'job_title' => 'Department Officer',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

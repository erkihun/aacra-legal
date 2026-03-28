<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('renders the correct dashboard context for each seeded role', function (string $email, string $roleKey): void {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('role_context.key', $roleKey)
            ->has('metrics')
            ->has('cases_by_status')
            ->has('recently_updated_matters'));
})->with([
    ['admin@ldms.test', 'super_admin'],
    ['director@ldms.test', 'legal_director'],
    ['litigation.lead@ldms.test', 'litigation_team_leader'],
    ['advisory.lead@ldms.test', 'advisory_team_leader'],
    ['expert.one@ldms.test', 'legal_expert'],
    ['requester@ldms.test', 'department_requester'],
    ['registrar@ldms.test', 'registrar'],
    ['auditor@ldms.test', 'auditor'],
]);

it('limits audit log access to authorized roles', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $auditor = User::query()->where('email', 'auditor@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($director)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('AuditLogs/Index'));

    $this->actingAs($auditor)
        ->get(route('audit-logs.index'))
        ->assertOk();

    $this->actingAs($requester)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

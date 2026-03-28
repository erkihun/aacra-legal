<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('renders the reports page for authorized users', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();

    $this->actingAs($director)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports/Index')
            ->has('cases_by_status')
            ->has('advisory_by_department')
            ->has('expert_workload')
            ->has('turnaround.rows')
            ->has('hearing_schedule')
            ->has('overdue_items'));
});

it('exports report datasets for users with export permission', function (): void {
    Excel::fake();

    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();

    $this->actingAs($director)
        ->get(route('reports.export', 'hearing-schedule'))
        ->assertOk();

    Excel::assertDownloaded('hearing-schedule.xlsx');
});

it('forbids report access for users without report permission', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('reports.index'))
        ->assertForbidden();
});

it('forbids exports for users without export permission', function (): void {
    Excel::fake();

    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('reports.export', 'cases-by-status'))
        ->assertForbidden();
});

it('validates report filters against whitelisted values', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();

    $this->actingAs($director)
        ->from(route('reports.index'))
        ->get(route('reports.index', [
            'status' => 'closed); drop table users; --',
        ]))
        ->assertRedirect(route('reports.index'))
        ->assertSessionHasErrors('status');
});

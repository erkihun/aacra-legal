<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryRequest;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

function paginationUser(SystemRole $role): User
{
    $department = Department::query()->where('code', 'LEG')->firstOrFail();
    $team = Team::query()->where('code', 'ADM')->first();

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

it('paginates the cases list and exposes page links', function (): void {
    $director = paginationUser(SystemRole::LEGAL_DIRECTOR);
    $registrar = paginationUser(SystemRole::REGISTRAR);

    $court = Court::query()->firstOrFail();
    $caseType = CaseType::query()->firstOrFail();

    foreach (range(1, 25) as $i) {
        LegalCase::query()->create([
            'case_number' => sprintf('CASE-PAGE-%04d', $i),
            'court_id' => $court->id,
            'case_type_id' => $caseType->id,
            'registered_by_id' => $registrar->id,
            'plaintiff' => "Plaintiff {$i}",
            'defendant' => "Defendant {$i}",
            'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
            'workflow_stage' => WorkflowStage::DIRECTOR,
            'priority' => PriorityLevel::MEDIUM,
            'director_decision' => 'pending',
            'claim_summary' => "Pagination fixture {$i}.",
            'filing_date' => now()->toDateString(),
        ]);
    }

    $this->actingAs($director)
        ->get(route('cases.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 10)
            ->where('cases.meta.total', 25)
            ->where('cases.meta.last_page', 3)
            ->where('cases.meta.current_page', 1)
            ->has('cases.meta.links'));

    // Page 2 must return a different slice, not the same first ten.
    $firstPage = $this->actingAs($director)->get(route('cases.index'))
        ->viewData('page')['props']['cases']['data'];
    $secondPage = $this->actingAs($director)->get(route('cases.index', ['page' => 2]))
        ->viewData('page')['props']['cases']['data'];

    $firstIds = collect($firstPage)->pluck('id');
    $secondIds = collect($secondPage)->pluck('id');

    expect($secondIds)->toHaveCount(10)
        ->and($firstIds->intersect($secondIds))->toBeEmpty();
});

it('keeps filters applied across pages of the cases list', function (): void {
    $director = paginationUser(SystemRole::LEGAL_DIRECTOR);
    $registrar = paginationUser(SystemRole::REGISTRAR);

    $court = Court::query()->firstOrFail();
    $caseType = CaseType::query()->firstOrFail();

    foreach (range(1, 15) as $i) {
        LegalCase::query()->create([
            'case_number' => sprintf('FILTER-%04d', $i),
            'court_id' => $court->id,
            'case_type_id' => $caseType->id,
            'registered_by_id' => $registrar->id,
            'plaintiff' => 'Findable Plaintiff',
            'defendant' => "Defendant {$i}",
            'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
            'workflow_stage' => WorkflowStage::DIRECTOR,
            'priority' => PriorityLevel::MEDIUM,
            'director_decision' => 'pending',
            'claim_summary' => 'Filtered fixture.',
            'filing_date' => now()->toDateString(),
        ]);
    }

    $this->actingAs($director)
        ->get(route('cases.index', ['search' => 'Findable', 'page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 5)
            ->where('cases.meta.total', 15)
            ->where('cases.meta.current_page', 2));
});

it('paginates the advisory list and exposes page links', function (): void {
    $director = paginationUser(SystemRole::LEGAL_DIRECTOR);

    AdvisoryRequest::factory()->count(22)->create([
        'status' => AdvisoryRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($director)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->has('requests.data', 10)
            ->where('requests.meta.total', 22)
            ->where('requests.meta.last_page', 3)
            ->has('requests.meta.links'));

    $firstPage = $this->actingAs($director)->get(route('advisory.index'))
        ->viewData('page')['props']['requests']['data'];
    $secondPage = $this->actingAs($director)->get(route('advisory.index', ['page' => 2]))
        ->viewData('page')['props']['requests']['data'];

    expect(collect($firstPage)->pluck('id')->intersect(collect($secondPage)->pluck('id')))->toBeEmpty();
});

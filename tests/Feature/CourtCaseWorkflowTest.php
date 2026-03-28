<?php

declare(strict_types=1);

use App\Enums\CaseStatus;
use App\Enums\SystemRole;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('moves a court case through registrar director team leader expert and closure', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $director = createCaseUserWithRole(SystemRole::LEGAL_DIRECTOR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $teamLeader = createCaseUserWithRole(SystemRole::LITIGATION_TEAM_LEADER, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());
    $expert = createCaseUserWithRole(SystemRole::LEGAL_EXPERT, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());

    $this->actingAs($registrar)->post(route('cases.store'), [
        'external_court_file_number' => 'FHC-99887',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'plaintiff' => 'Employee B',
        'defendant' => 'Institution',
        'bench_or_chamber' => 'Bench 3',
        'claim_summary' => 'The claimant alleges unlawful termination and unpaid benefits.',
        'institution_position' => 'Termination followed internal discipline rules.',
        'filing_date' => now()->subWeek()->toDateString(),
        'next_hearing_date' => now()->addWeek()->toDateString(),
        'priority' => 'critical',
    ])->assertRedirect();

    $legalCase = LegalCase::query()->firstOrFail();

    expect($legalCase->status)->toBe(CaseStatus::UNDER_DIRECTOR_REVIEW);

    $this->actingAs($director)->patch(route('cases.review', $legalCase), [
        'director_decision' => 'approved',
        'director_notes' => 'Assign for immediate litigation handling.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_TEAM_LEADER);

    $this->actingAs($teamLeader)->patch(route('cases.assign', $legalCase), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Prepare witness schedule and payroll exhibits.',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_EXPERT);

    $this->actingAs($expert)->post(route('cases.hearings.store', $legalCase), [
        'hearing_date' => now()->toDateString(),
        'next_hearing_date' => now()->addDays(10)->toDateString(),
        'appearance_status' => 'attended',
        'summary' => 'Court heard preliminary objections and requested payroll records.',
        'institution_position' => 'Institution requested time to file supporting documents.',
        'court_decision' => 'Continued to next hearing.',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::IN_PROGRESS);
    expect($legalCase->hearings)->toHaveCount(1);

    $this->actingAs($teamLeader)->patch(route('cases.close', $legalCase), [
        'outcome' => 'Case closed after negotiated settlement.',
        'decision_date' => now()->toDateString(),
        'appeal_deadline' => now()->addDays(30)->toDateString(),
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::CLOSED);
});

function createCaseUserWithRole(SystemRole $role, Department $department, ?Team $team = null): User
{
    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

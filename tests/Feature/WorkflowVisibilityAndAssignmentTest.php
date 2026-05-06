<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
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

it('lets a director view all advisory records across teams', function (): void {
    $director = createScopedUser(SystemRole::LEGAL_DIRECTOR, 'LEG', 'ADM');
    [$leaderA, $expertA] = createAdvisoryTeamMembers('ADV', 'advisory-a');
    [$leaderB, $expertB] = createAdvisoryTeamMembers('ADV2', 'advisory-b');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $first = createAssignedAdvisory('ADV-DIR-001', $requester, $leaderA, $expertA);
    $second = createAssignedAdvisory('ADV-DIR-002', $requester, $leaderB, $expertB);

    $this->actingAs($director)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->has('requests.data', 2)
        );

    $this->actingAs($director)->get(route('advisory.show', $first))->assertOk();
    $this->actingAs($director)->get(route('advisory.show', $second))->assertOk();
});

it('limits advisory team leaders to records from their own team only', function (): void {
    [$leaderA, $expertA] = createAdvisoryTeamMembers('ADV', 'advisory-team-a');
    [$leaderB, $expertB] = createAdvisoryTeamMembers('ADV2', 'advisory-team-b');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $ownRequest = createAssignedAdvisory('ADV-TEAM-001', $requester, $leaderA, $expertA);
    $otherRequest = createAssignedAdvisory('ADV-TEAM-002', $requester, $leaderB, $expertB);

    $this->actingAs($leaderA)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $ownRequest->id)
        );

    $this->actingAs($leaderA)->get(route('advisory.show', $ownRequest))->assertOk();
    $this->actingAs($leaderA)->get(route('advisory.show', $otherRequest))->assertForbidden();
});

it('limits advisory experts to their own assigned records only', function (): void {
    [$leader, $expertA] = createAdvisoryTeamMembers('ADV', 'advisory-member-a');
    $expertB = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'ADV', 'advisory-member-b@ldms.test');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $ownRequest = createAssignedAdvisory('ADV-MEMBER-001', $requester, $leader, $expertA);
    $otherRequest = createAssignedAdvisory('ADV-MEMBER-002', $requester, $leader, $expertB);

    $this->actingAs($expertA)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $ownRequest->id)
        );

    $this->actingAs($expertA)->get(route('advisory.show', $ownRequest))->assertOk();
    $this->actingAs($expertA)->get(route('advisory.show', $otherRequest))->assertForbidden();
});

it('lets a director view all litigation records across teams', function (): void {
    $director = createScopedUser(SystemRole::LEGAL_DIRECTOR, 'LEG', 'ADM');
    [$leaderA, $expertA] = createLitigationTeamMembers('LIT', 'litigation-a');
    [$leaderB, $expertB] = createLitigationTeamMembers('LIT2', 'litigation-b');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'registrar-all@ldms.test');

    $first = createAssignedCase('CASE-DIR-001', $registrar, $leaderA, $expertA);
    $second = createAssignedCase('CASE-DIR-002', $registrar, $leaderB, $expertB);

    $this->actingAs($director)
        ->get(route('cases.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 2)
        );

    $this->actingAs($director)->get(route('cases.show', $first))->assertOk();
    $this->actingAs($director)->get(route('cases.show', $second))->assertOk();
});

it('limits litigation team leaders to records from their own team only', function (): void {
    [$leaderA, $expertA] = createLitigationTeamMembers('LIT', 'litigation-team-a');
    [$leaderB, $expertB] = createLitigationTeamMembers('LIT2', 'litigation-team-b');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'registrar-team@ldms.test');

    $ownCase = createAssignedCase('CASE-TEAM-001', $registrar, $leaderA, $expertA);
    $otherCase = createAssignedCase('CASE-TEAM-002', $registrar, $leaderB, $expertB);

    $this->actingAs($leaderA)
        ->get(route('cases.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $ownCase->id)
        );

    $this->actingAs($leaderA)->get(route('cases.show', $ownCase))->assertOk();
    $this->actingAs($leaderA)->get(route('cases.show', $otherCase))->assertForbidden();
});

it('limits litigation experts to their own assigned records only', function (): void {
    [$leader, $expertA] = createLitigationTeamMembers('LIT', 'litigation-member-a');
    $expertB = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'LIT', 'litigation-member-b@ldms.test');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'registrar-own@ldms.test');

    $ownCase = createAssignedCase('CASE-MEMBER-001', $registrar, $leader, $expertA);
    $otherCase = createAssignedCase('CASE-MEMBER-002', $registrar, $leader, $expertB);

    $this->actingAs($expertA)
        ->get(route('cases.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $ownCase->id)
        );

    $this->actingAs($expertA)->get(route('cases.show', $ownCase))->assertOk();
    $this->actingAs($expertA)->get(route('cases.show', $otherCase))->assertForbidden();
});

it('shows only actual team leaders in the director assignment dropdowns', function (): void {
    $director = createScopedUser(SystemRole::LEGAL_DIRECTOR, 'LEG', 'ADM');
    [$advisoryLeader] = createAdvisoryTeamMembers('ADV', 'leader-only-advisory');
    [$litigationLeader] = createLitigationTeamMembers('LIT', 'leader-only-litigation');
    $advisoryNonLeader = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'ADV', 'advisory-non-leader@ldms.test');
    $litigationNonLeader = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'LIT', 'litigation-non-leader@ldms.test');
    $advisoryNonLeader->givePermissionTo('advisory.assign_expert');
    $litigationNonLeader->givePermissionTo('cases.assign_expert');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'dropdown-registrar@ldms.test');

    $advisory = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-DROPDOWN-001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Director dropdown advisory',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'description' => 'Only actual team leaders should appear.',
        'date_submitted' => now()->toDateString(),
    ]);

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-DROPDOWN-001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Dropdown Plaintiff',
        'defendant' => 'Dropdown Defendant',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => 'Only actual litigation leaders should appear.',
    ]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisory))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('teamLeaders', fn ($leaders) => collect($leaders)->pluck('id')->all() === [$advisoryLeader->id])
        );

    $this->actingAs($director)
        ->get(route('cases.show', $legalCase))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('teamLeaders', fn ($leaders) => collect($leaders)->pluck('id')->all() === [$litigationLeader->id])
        );
});

it('limits team leader assignee dropdowns to members of their own team', function (): void {
    [$advisoryLeader, $advisoryExpert] = createAdvisoryTeamMembers('ADV', 'advisory-own-team');
    [, $otherAdvisoryExpert] = createAdvisoryTeamMembers('ADV2', 'advisory-other-team');
    [$litigationLeader, $litigationExpert] = createLitigationTeamMembers('LIT', 'litigation-own-team');
    [, $otherLitigationExpert] = createLitigationTeamMembers('LIT2', 'litigation-other-team');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'scope-registrar@ldms.test');

    $advisory = createTeamLeaderStageAdvisory('ADV-ASSIGN-001', $requester, $advisoryLeader);
    $legalCase = createTeamLeaderStageCase('CASE-ASSIGN-001', $registrar, $litigationLeader);

    $this->actingAs($advisoryLeader)
        ->get(route('advisory.show', $advisory))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('experts', fn ($experts) => collect($experts)->pluck('id')->all() === [$advisoryExpert->id])
        );

    $this->actingAs($litigationLeader)
        ->get(route('cases.show', $legalCase))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('experts', fn ($experts) => collect($experts)->pluck('id')->all() === [$litigationExpert->id])
        );

    expect($otherAdvisoryExpert->id)->not->toBe($advisoryExpert->id);
    expect($otherLitigationExpert->id)->not->toBe($litigationExpert->id);
});

it('rejects forged advisory expert assignments outside the team leaders team', function (): void {
    [$leader, $ownExpert] = createAdvisoryTeamMembers('ADV', 'advisory-forged-own');
    [, $foreignExpert] = createAdvisoryTeamMembers('ADV2', 'advisory-forged-foreign');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $advisory = createTeamLeaderStageAdvisory('ADV-FORGED-001', $requester, $leader);

    $this->actingAs($leader)
        ->patch(route('advisory.assign', $advisory), [
            'assigned_legal_expert_id' => $foreignExpert->id,
        ])
        ->assertSessionHasErrors('assigned_legal_expert_id');

    expect($advisory->fresh()->assigned_legal_expert_id)->toBeNull();
    expect($ownExpert->id)->not->toBe($foreignExpert->id);
});

it('rejects forged litigation expert assignments outside the team leaders team', function (): void {
    [$leader] = createLitigationTeamMembers('LIT', 'litigation-forged-own');
    [, $foreignExpert] = createLitigationTeamMembers('LIT2', 'litigation-forged-foreign');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'forged-registrar@ldms.test');

    $legalCase = createTeamLeaderStageCase('CASE-FORGED-001', $registrar, $leader);

    $this->actingAs($leader)
        ->patch(route('cases.assign', $legalCase), [
            'assigned_legal_expert_id' => $foreignExpert->id,
        ])
        ->assertSessionHasErrors('assigned_legal_expert_id');

    expect($legalCase->fresh()->assigned_legal_expert_id)->toBeNull();
});

function createScopedUser(
    SystemRole $role,
    string $departmentCode,
    ?string $teamCode = null,
    ?string $email = null,
): User {
    $department = Department::query()->where('code', strtoupper($departmentCode))->firstOrFail();
    $team = $teamCode !== null ? findOrCreateTeam($teamCode) : null;

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => $email ?? fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);
    syncTestTeamLeadership($user, $team, $role);

    return $user;
}

function findOrCreateTeam(string $code): Team
{
    $normalized = strtoupper($code);

    return Team::query()->firstOrCreate(
        ['code' => $normalized],
        [
            'name_en' => "{$normalized} Team",
            'name_am' => "{$normalized} ቡድን",
            'type' => str_starts_with($normalized, 'LIT') ? TeamType::LITIGATION : TeamType::ADVISORY,
            'supports_advisory' => ! str_starts_with($normalized, 'LIT'),
            'supports_court_case' => str_starts_with($normalized, 'LIT'),
            'is_active' => true,
        ],
    );
}

function createAdvisoryTeamMembers(string $teamCode, string $emailPrefix): array
{
    $team = findOrCreateTeam($teamCode);
    $team->forceFill([
        'supports_advisory' => true,
        'supports_court_case' => (bool) $team->supports_court_case,
    ])->save();

    $leader = createScopedUser(SystemRole::ADVISORY_TEAM_LEADER, 'LEG', $teamCode, "{$emailPrefix}-leader@ldms.test");
    $expert = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', $teamCode, "{$emailPrefix}-expert@ldms.test");

    return [$leader, $expert];
}

function createLitigationTeamMembers(string $teamCode, string $emailPrefix): array
{
    $team = findOrCreateTeam($teamCode);
    $team->forceFill([
        'supports_advisory' => (bool) $team->supports_advisory,
        'supports_court_case' => true,
    ])->save();

    $leader = createScopedUser(SystemRole::LITIGATION_TEAM_LEADER, 'LEG', $teamCode, "{$emailPrefix}-leader@ldms.test");
    $expert = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', $teamCode, "{$emailPrefix}-expert@ldms.test");

    return [$leader, $expert];
}

function createAssignedAdvisory(string $number, User $requester, User $leader, User $expert): AdvisoryRequest
{
    return AdvisoryRequest::query()->create([
        'request_number' => $number,
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $leader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => "Subject {$number}",
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'description' => "Description {$number}",
        'date_submitted' => now()->toDateString(),
    ]);
}

function createAssignedCase(string $number, User $registrar, User $leader, User $expert): LegalCase
{
    return LegalCase::query()->create([
        'case_number' => $number,
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $leader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => "Plaintiff {$number}",
        'defendant' => "Defendant {$number}",
        'status' => CaseStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'claim_summary' => "Summary {$number}",
    ]);
}

function createTeamLeaderStageAdvisory(string $number, User $requester, User $leader): AdvisoryRequest
{
    return AdvisoryRequest::query()->create([
        'request_number' => $number,
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $leader->id,
        'subject' => "Subject {$number}",
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'description' => "Description {$number}",
        'date_submitted' => now()->toDateString(),
    ]);
}

function createTeamLeaderStageCase(string $number, User $registrar, User $leader): LegalCase
{
    return LegalCase::query()->create([
        'case_number' => $number,
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $leader->id,
        'plaintiff' => "Plaintiff {$number}",
        'defendant' => "Defendant {$number}",
        'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'claim_summary' => "Summary {$number}",
    ]);
}

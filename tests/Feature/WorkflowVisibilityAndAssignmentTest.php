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
use App\Models\CaseHearing;
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

it('does not widen advisory visibility for team leaders through audit permission', function (): void {
    [$leaderA, $expertA] = createAdvisoryTeamMembers('ADV', 'advisory-audit-a');
    [$leaderB, $expertB] = createAdvisoryTeamMembers('ADV2', 'advisory-audit-b');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $leaderA->givePermissionTo('audit.view');

    $ownRequest = createAssignedAdvisory('ADV-AUDIT-001', $requester, $leaderA, $expertA);
    $otherRequest = createAssignedAdvisory('ADV-AUDIT-002', $requester, $leaderB, $expertB);

    $this->actingAs($leaderA)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $ownRequest->id)
        );

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

it('does not widen litigation visibility for team leaders through audit permission', function (): void {
    [$leaderA, $expertA] = createLitigationTeamMembers('LIT', 'litigation-audit-a');
    [$leaderB, $expertB] = createLitigationTeamMembers('LIT2', 'litigation-audit-b');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'audit-litigation-registrar@ldms.test');

    $leaderA->givePermissionTo('audit.view');

    $ownCase = createAssignedCase('CASE-AUDIT-001', $registrar, $leaderA, $expertA);
    $otherCase = createAssignedCase('CASE-AUDIT-002', $registrar, $leaderB, $expertB);

    $this->actingAs($leaderA)
        ->get(route('cases.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $ownCase->id)
        );

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

it('forbids members from acting on another members advisory and case records', function (): void {
    [$advisoryLeader, $advisoryExpertA] = createAdvisoryTeamMembers('ADV', 'advisory-act-own');
    $advisoryExpertB = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'ADV', 'advisory-act-other@ldms.test');
    [$litigationLeader, $litigationExpertA] = createLitigationTeamMembers('LIT', 'litigation-act-own');
    $litigationExpertB = createScopedUser(SystemRole::LEGAL_EXPERT, 'LEG', 'LIT', 'litigation-act-other@ldms.test');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'action-registrar@ldms.test');

    $otherAdvisory = createAssignedAdvisory('ADV-ACT-001', $requester, $advisoryLeader, $advisoryExpertB);
    $otherCase = createAssignedCase('CASE-ACT-001', $registrar, $litigationLeader, $litigationExpertB);

    $this->actingAs($advisoryExpertA)
        ->post(route('advisory.respond', $otherAdvisory), [
            'subject' => 'Unauthorized response',
            'response' => 'This response should be rejected because it is outside scope.',
        ])
        ->assertForbidden();

    $this->actingAs($litigationExpertA)
        ->post(route('cases.hearings.store', $otherCase), [
            'hearing_date' => now()->toDateString(),
            'summary' => 'This hearing should be rejected because it is outside scope.',
        ])
        ->assertForbidden();
});

it('forbids team leaders from acting on another teams advisory and case records', function (): void {
    [$advisoryLeaderA] = createAdvisoryTeamMembers('ADV', 'advisory-leader-act-own');
    [$advisoryLeaderB, $advisoryExpertB] = createAdvisoryTeamMembers('ADV2', 'advisory-leader-act-other');
    [$litigationLeaderA] = createLitigationTeamMembers('LIT', 'litigation-leader-act-own');
    [$litigationLeaderB, $litigationExpertB] = createLitigationTeamMembers('LIT2', 'litigation-leader-act-other');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'leader-action-registrar@ldms.test');

    $foreignAdvisory = createTeamLeaderStageAdvisory('ADV-LEADER-ACT-001', $requester, $advisoryLeaderB);
    $foreignCase = createTeamLeaderStageCase('CASE-LEADER-ACT-001', $registrar, $litigationLeaderB);

    $this->actingAs($advisoryLeaderA)
        ->patch(route('advisory.assign', $foreignAdvisory), [
            'assigned_legal_expert_id' => $advisoryExpertB->id,
        ])
        ->assertForbidden();

    $this->actingAs($litigationLeaderA)
        ->patch(route('cases.assign', $foreignCase), [
            'assigned_legal_expert_id' => $litigationExpertB->id,
        ])
        ->assertForbidden();
});

it('inherits parent case access rules for hearing comment actions', function (): void {
    [$leaderA] = createLitigationTeamMembers('LIT', 'litigation-hearing-own');
    [$leaderB, $expertB] = createLitigationTeamMembers('LIT2', 'litigation-hearing-other');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'hearing-comment-registrar@ldms.test');

    $otherCase = createAssignedCase('CASE-HEARING-001', $registrar, $leaderB, $expertB);
    $hearing = CaseHearing::query()->create([
        'legal_case_id' => $otherCase->id,
        'recorded_by_id' => $expertB->id,
        'hearing_date' => now()->toDateString(),
        'summary' => 'Foreign team hearing summary.',
    ]);

    $this->actingAs($leaderA)
        ->post(route('cases.hearings.comments.store', [$otherCase, $hearing]), [
            'body' => 'This comment must be denied.',
            'is_internal' => true,
        ])
        ->assertForbidden();
});

it('keeps legacy unassigned advisory and case records from leaking or crashing scoped lists', function (): void {
    [$advisoryLeader, $advisoryExpert] = createAdvisoryTeamMembers('ADV', 'legacy-advisory');
    [$litigationLeader, $litigationExpert] = createLitigationTeamMembers('LIT', 'legacy-litigation');
    $requester = createScopedUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $registrar = createScopedUser(SystemRole::REGISTRAR, 'LEG', 'ADM', 'legacy-registrar@ldms.test');

    $unassignedAdvisory = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-LEGACY-001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Legacy advisory',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'description' => 'Legacy advisory without assignments.',
        'date_submitted' => now()->toDateString(),
    ]);

    $unassignedCase = LegalCase::query()->create([
        'case_number' => 'CASE-LEGACY-001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Legacy Plaintiff',
        'defendant' => 'Legacy Defendant',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => 'Legacy case without assignments.',
    ]);

    $this->actingAs($advisoryLeader)->get(route('advisory.index'))->assertOk();
    $this->actingAs($advisoryExpert)->get(route('advisory.index'))->assertOk();
    $this->actingAs($litigationLeader)->get(route('cases.index'))->assertOk();
    $this->actingAs($litigationExpert)->get(route('cases.index'))->assertOk();

    $this->actingAs($advisoryLeader)->get(route('advisory.show', $unassignedAdvisory))->assertForbidden();
    $this->actingAs($advisoryExpert)->get(route('advisory.show', $unassignedAdvisory))->assertForbidden();
    $this->actingAs($litigationLeader)->get(route('cases.show', $unassignedCase))->assertForbidden();
    $this->actingAs($litigationExpert)->get(route('cases.show', $unassignedCase))->assertForbidden();
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

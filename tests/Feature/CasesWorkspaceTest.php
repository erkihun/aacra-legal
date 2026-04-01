<?php

declare(strict_types=1);

use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('executes the full legal case workspace actions', function (): void {
    Storage::fake('public');

    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $teamLeader = createCaseUser(SystemRole::LITIGATION_TEAM_LEADER, 'leg', 'LIT');
    $expert = createCaseUser(SystemRole::LEGAL_EXPERT, 'leg', 'LIT');

    $director->givePermissionTo(['comments.create', 'attachments.create']);

    $court = Court::query()->firstOrFail();
    $caseType = CaseType::query()->firstOrFail();

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9001',
        'court_id' => $court->id,
        'case_type_id' => $caseType->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Alpha Corp',
        'defendant' => 'Beta PLC',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'pending',
        'claim_summary' => 'Contract dispute over delayed delivery.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($director)->patch(route('cases.review', ['legalCase' => $legalCase]), [
        'director_decision' => 'approved',
        'director_notes' => 'Assign to litigation lead.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();
    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_TEAM_LEADER);
    expect($legalCase->assigned_team_leader_id)->toBe($teamLeader->id);

    $this->actingAs($teamLeader)->patch(route('cases.assign', ['legalCase' => $legalCase]), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Handle hearings.',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();
    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_EXPERT);
    expect($legalCase->assigned_legal_expert_id)->toBe($expert->id);

    $this->actingAs($expert)->post(route('cases.hearings.store', ['legalCase' => $legalCase]), [
        'hearing_date' => now()->toDateString(),
        'next_hearing_date' => null,
        'appearance_status' => 'attended',
        'summary' => 'Initial hearing recorded.',
        'institution_position' => 'Defend claim',
        'court_decision' => 'Adjourned for submissions',
        'outcome' => 'Pending judgment',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();
    expect(in_array($legalCase->status, [CaseStatus::IN_PROGRESS, CaseStatus::DECIDED], true))->toBeTrue();

    $this->actingAs($director)->patch(route('cases.close', ['legalCase' => $legalCase]), [
        'outcome' => 'Settled',
        'decision_date' => now()->toDateString(),
        'appeal_deadline' => now()->addDays(30)->toDateString(),
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();
    expect($legalCase->status)->toBe(CaseStatus::CLOSED);

    $this->actingAs($director)->post(route('cases.comments.store', ['legalCase' => $legalCase]), [
        'body' => 'Case closed after settlement.',
        'is_internal' => true,
    ])->assertSessionHasNoErrors();

    $this->actingAs($director)->post(route('cases.attachments.store', ['legalCase' => $legalCase]), [
        'attachments' => [UploadedFile::fake()->create('settlement.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();
    expect($legalCase->comments()->count())->toBe(1);
    expect($legalCase->attachments()->count())->toBe(1);
});

it('provides the legal case id to the case show page props', function (): void {
    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9002',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Gamma PLC',
        'defendant' => 'Delta Enterprise',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => 'Verify Inertia case payload exposes the legal case id.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('cases.show', ['legalCase' => $legalCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.id', $legalCase->id)
        );
});

it('prevents assigning a case team leader more than once through the normal review flow', function (): void {
    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $firstLeader = createCaseUser(SystemRole::LITIGATION_TEAM_LEADER, 'leg', 'LIT');
    $secondLeader = createCaseUser(SystemRole::LITIGATION_TEAM_LEADER, 'leg', 'LIT');

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9003',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $firstLeader->id,
        'plaintiff' => 'Duplicate Team Lead',
        'defendant' => 'Institution',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'pending',
        'claim_summary' => 'Prevent duplicate team leader assignment on a case.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($director)->patch(route('cases.review', ['legalCase' => $legalCase]), [
        'director_decision' => 'approved',
        'director_notes' => 'Attempt duplicate team leader assignment.',
        'assigned_team_leader_id' => $secondLeader->id,
    ])->assertSessionHasErrors('assigned_team_leader_id');

    expect($legalCase->fresh()->assigned_team_leader_id)->toBe($firstLeader->id);
});

it('prevents assigning a case expert more than once through the normal assignment flow', function (): void {
    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $teamLeader = createCaseUser(SystemRole::LITIGATION_TEAM_LEADER, 'leg', 'LIT');
    $firstExpert = createCaseUser(SystemRole::LEGAL_EXPERT, 'leg', 'LIT');
    $secondExpert = createCaseUser(SystemRole::LEGAL_EXPERT, 'leg', 'LIT');

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9004',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $firstExpert->id,
        'plaintiff' => 'Duplicate Expert',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'approved',
        'claim_summary' => 'Prevent duplicate expert assignment on a case.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($teamLeader)->patch(route('cases.assign', ['legalCase' => $legalCase]), [
        'assigned_legal_expert_id' => $secondExpert->id,
        'notes' => 'Attempt duplicate expert assignment.',
    ])->assertSessionHasErrors('assigned_legal_expert_id');

    expect($legalCase->fresh()->assigned_legal_expert_id)->toBe($firstExpert->id);
});

it('hides case assignment workspace forms once assignment already exists', function (): void {
    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $teamLeader = createCaseUser(SystemRole::LITIGATION_TEAM_LEADER, 'leg', 'LIT');
    $expert = createCaseUser(SystemRole::LEGAL_EXPERT, 'leg', 'LIT');

    $teamLeaderAssignedCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9005',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'plaintiff' => 'Workspace Director Hide',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'claim_summary' => 'Director assignment UI must stay hidden after a team leader is already assigned.',
        'filing_date' => now()->toDateString(),
    ]);

    $expertAssignedCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9006',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Workspace Expert Hide',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'approved',
        'claim_summary' => 'Expert assignment UI must stay hidden after an expert is already assigned.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('cases.show', ['legalCase' => $teamLeaderAssignedCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('workspace.canAssignTeamLeader', false)
            ->where('workspace.canAssignExpert', false)
        );

    $this->actingAs($teamLeader)
        ->get(route('cases.show', ['legalCase' => $expertAssignedCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('workspace.canAssignTeamLeader', false)
            ->where('workspace.canAssignExpert', false)
        );
});

function createCaseUser(SystemRole $role, string $departmentCode, ?string $teamCode = null): User
{
    $department = Department::query()->where('code', strtoupper($departmentCode))->firstOrFail();
    $team = $teamCode ? Team::query()->where('code', strtoupper($teamCode))->firstOrFail() : null;

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

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
use App\Models\Attachment;
use App\Models\Comment;
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

    $director->givePermissionTo(['comments.create', 'attachments.create', 'attachments.delete']);

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
        'court_decision' => 'Adjourned for submissions',
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

it('exposes case comments and attachments as plain arrays on the show payload', function (): void {
    Storage::fake('public');

    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $director->givePermissionTo(['comments.create', 'attachments.create']);

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9007',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Payload Shape',
        'defendant' => 'Institution',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => 'Verify comment and attachment arrays are flattened for the case show payload.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($director)->post(route('cases.comments.store', ['legalCase' => $legalCase]), [
        'body' => 'Payload comment',
        'is_internal' => true,
    ])->assertSessionHasNoErrors();

    $this->actingAs($director)->post(route('cases.attachments.store', ['legalCase' => $legalCase]), [
        'attachments' => [UploadedFile::fake()->create('payload.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $this->actingAs($director)
        ->get(route('cases.show', ['legalCase' => $legalCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->has('caseItem.comments', 1)
            ->has('caseItem.attachments', 1)
            ->where('caseItem.comments.0.body', 'Payload comment')
            ->where('caseItem.attachments.0.original_name', 'payload.pdf')
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

it('updates and deletes case hearings comments and attachments through the workspace endpoints', function (): void {
    Storage::fake('public');

    $registrar = createCaseUser(SystemRole::REGISTRAR, 'leg', 'ADM');
    $director = createCaseUser(SystemRole::LEGAL_DIRECTOR, 'leg', 'ADM');
    $expert = createCaseUser(SystemRole::LEGAL_EXPERT, 'leg', 'LIT');
    $superAdmin = createCaseUser(SystemRole::SUPER_ADMIN, 'leg', 'ADM');

    $director->givePermissionTo(['comments.create', 'attachments.create']);
    $expert->givePermissionTo(['attachments.create']);

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-9010',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Workspace Update',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'approved',
        'claim_summary' => 'Exercise workspace edit and delete endpoints.',
        'filing_date' => now()->toDateString(),
    ]);

    $this->actingAs($expert)->post(route('cases.hearings.store', ['legalCase' => $legalCase]), [
        'hearing_date' => now()->toDateString(),
        'next_hearing_date' => now()->addWeek()->toDateString(),
        'appearance_status' => 'attended',
        'summary' => 'Initial hearing details for editing.',
        'court_decision' => 'Adjourned',
    ])->assertSessionHasNoErrors();

    $hearing = $legalCase->hearings()->firstOrFail();

    $this->actingAs($expert)->patch(route('cases.hearings.update', [
        'legalCase' => $legalCase,
        'hearing' => $hearing,
    ]), [
        'hearing_date' => now()->addDay()->toDateString(),
        'next_hearing_date' => now()->addWeeks(2)->toDateString(),
        'appearance_status' => 'attended',
        'summary' => 'Updated hearing summary with enough detail.',
        'court_decision' => 'Proceed to submissions',
    ])->assertSessionHasNoErrors();

    expect($hearing->fresh()->summary)->toBe('Updated hearing summary with enough detail.');

    $this->actingAs($director)->post(route('cases.comments.store', ['legalCase' => $legalCase]), [
        'body' => 'Initial internal note for editing.',
        'is_internal' => true,
    ])->assertSessionHasNoErrors();

    $comment = $legalCase->comments()->firstOrFail();

    $this->actingAs($director)->patch(route('cases.comments.update', [
        'legalCase' => $legalCase,
        'comment' => $comment,
    ]), [
        'body' => 'Updated internal note for the case.',
    ])->assertSessionHasNoErrors();

    expect($comment->fresh()->body)->toBe('Updated internal note for the case.');

    $this->actingAs($director)->post(route('cases.attachments.store', ['legalCase' => $legalCase]), [
        'attachments' => [UploadedFile::fake()->create('initial-evidence.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    /** @var Attachment $attachment */
    $attachment = $legalCase->attachments()->firstOrFail();

    $this->actingAs($director)->patch(route('attachments.update', ['attachment' => $attachment]), [
        'original_name' => 'renamed-evidence.pdf',
    ])->assertSessionHasNoErrors();

    expect($attachment->fresh()->original_name)->toBe('renamed-evidence.pdf');

    $this->actingAs($expert)->delete(route('cases.hearings.destroy', [
        'legalCase' => $legalCase,
        'hearing' => $hearing,
    ]))->assertSessionHasNoErrors();

    $this->actingAs($director)->delete(route('cases.comments.destroy', [
        'legalCase' => $legalCase,
        'comment' => $comment,
    ]))->assertSessionHasNoErrors();

    $this->actingAs($superAdmin)->delete(route('attachments.destroy', ['attachment' => $attachment]))
        ->assertSessionHasNoErrors();

    expect($legalCase->hearings()->count())->toBe(0);
    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse();
    expect(Attachment::query()->whereKey($attachment->id)->exists())->toBeFalse();
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

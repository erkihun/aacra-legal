<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('exposes workflow creation actions only to authorized roles in list pages', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $registrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('advisory.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->where('can.create', true));

    $this->actingAs($director)
        ->get(route('advisory.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->where('can.create', false));

    $this->actingAs($registrar)
        ->get(route('cases.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->where('can.create', true));

    $this->actingAs($requester)
        ->get(route('cases.index'))
        ->assertForbidden();
});

it('enforces updated role permissions for advisory review actions', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail();

    $role->revokePermissionTo('advisory.review');
    $role->revokePermissionTo('advisory-requests.review');
    $director->refresh();

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ACCESS-0001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Permission controlled review',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::PENDING,
        'description' => 'The director should be blocked once the review permissions are removed.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->patch(route('advisory.review', $advisoryRequest), [
            'director_decision' => 'approved',
            'director_notes' => 'This should be forbidden.',
            'assigned_team_leader_id' => $teamLeader->id,
        ])
        ->assertForbidden();
});

it('enforces updated role permissions for case hearing actions', function (): void {
    $expert = User::query()->where('email', 'expert.one@ldms.test')->firstOrFail();
    $registrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();
    $litigationLeader = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_EXPERT->value)->firstOrFail();

    $role->revokePermissionTo('cases.record_hearing');
    $role->revokePermissionTo('legal-cases.update');
    $expert->refresh();

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-ACCESS-0001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $litigationLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Permission Gate',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'claim_summary' => 'The hearing action should be blocked when the role loses the hearing permission.',
        'filing_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($expert)
        ->post(route('cases.hearings.store', $legalCase), [
            'hearing_date' => now()->toDateString(),
            'appearance_status' => 'attended',
            'summary' => 'Attempted hearing entry.',
        ])
        ->assertForbidden();
});

it('requires comment permission in addition to matter visibility', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::DEPARTMENT_REQUESTER->value)->firstOrFail();

    $role->revokePermissionTo('comments.create');
    $requester->refresh();

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ACCESS-0002',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Comment permission check',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => DirectorDecision::PENDING,
        'description' => 'Commenting should be blocked without the comments.create permission.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($requester)
        ->post(route('advisory.comments.store', $advisoryRequest), [
            'body' => 'Attempted comment without permission.',
            'is_internal' => true,
        ])
        ->assertForbidden();
});

it('keeps advisory workflow actions working after role names are renamed when permissions remain assigned', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();

    Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail()->update([
        'name' => 'Operations Review Lead',
    ]);
    Role::query()->where('name', SystemRole::ADVISORY_TEAM_LEADER->value)->firstOrFail()->update([
        'name' => 'Advisory Workflow Lead',
    ]);

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ACCESS-RENAME-1',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Renamed role advisory review',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::PENDING,
        'description' => 'Workflow authorization should continue to work after renaming the seeded advisory roles.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can.review', true)
            ->where('workspace.canAssignTeamLeader', true)
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $teamLeader->id));

    $this->actingAs($director)
        ->patch(route('advisory.review', $advisoryRequest), [
            'director_decision' => 'approved',
            'director_notes' => 'Approved after role rename.',
            'assigned_team_leader_id' => $teamLeader->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($advisoryRequest->fresh()?->assigned_team_leader_id)->toBe($teamLeader->id);
});

it('keeps case workflow actions working after expert and leader role names are renamed when permissions remain assigned', function (): void {
    $registrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $expert = User::query()->where('email', 'expert.two@ldms.test')->firstOrFail();

    Role::query()->where('name', SystemRole::LITIGATION_TEAM_LEADER->value)->firstOrFail()->update([
        'name' => 'Litigation Workflow Lead',
    ]);
    Role::query()->where('name', SystemRole::LEGAL_EXPERT->value)->firstOrFail()->update([
        'name' => 'Case Strategy Expert',
    ]);

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-ACCESS-RENAME-1',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'plaintiff' => 'Renamed Role Plaintiff',
        'defendant' => 'Institution',
        'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'claim_summary' => 'Workflow authorization should continue to work after renaming the seeded case roles.',
        'filing_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($teamLeader)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can.assign', true)
            ->where('workspace.canAssignExpert', true)
            ->where('experts.0.id', $expert->id));

    $this->actingAs($teamLeader)
        ->patch(route('cases.assign', $legalCase), [
            'assigned_legal_expert_id' => $expert->id,
            'notes' => 'Assigned after role rename.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($legalCase->fresh()?->assigned_legal_expert_id)->toBe($expert->id);

    $this->actingAs($expert)
        ->post(route('cases.hearings.store', $legalCase->fresh()), [
            'hearing_date' => now()->toDateString(),
            'appearance_status' => 'attended',
            'summary' => 'Recorded by renamed expert role.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

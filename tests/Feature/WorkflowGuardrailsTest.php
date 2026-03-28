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

it('prevents a requester from submitting an advisory request for another department', function (): void {
    $requester = createGuardrailUser(SystemRole::DEPARTMENT_REQUESTER, 'guard-requester@ldms.test', 'HR');
    $otherDepartment = Department::query()->where('code', 'PROC')->firstOrFail();

    $this->actingAs($requester)
        ->post(route('advisory.store'), [
            'department_id' => $otherDepartment->id,
            'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
            'subject' => 'Cross department request',
            'request_type' => 'written',
            'priority' => 'medium',
            'description' => 'This request should be rejected because it targets another department.',
        ])
        ->assertSessionHasErrors('department_id');

    expect(AdvisoryRequest::query()->count())->toBe(0);
});

it('prevents advisory assignment to an expert outside the advisory team', function (): void {
    $requester = createGuardrailUser(SystemRole::DEPARTMENT_REQUESTER, 'advisory-requester@ldms.test', 'HR');
    $director = createGuardrailUser(SystemRole::LEGAL_DIRECTOR, 'advisory-director@ldms.test', 'LEG', 'ADM');
    $teamLeader = createGuardrailUser(SystemRole::ADVISORY_TEAM_LEADER, 'advisory-leader@ldms.test', 'LEG', 'ADV');
    $wrongExpert = createGuardrailUser(SystemRole::LEGAL_EXPERT, 'litigation-expert@ldms.test', 'LEG', 'LIT');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-GUARD-0001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'subject' => 'Assignment guard',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'description' => 'Prevent assignment to a litigation expert.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($teamLeader)
        ->patch(route('advisory.assign', $advisoryRequest), [
            'assigned_legal_expert_id' => $wrongExpert->id,
            'notes' => 'Invalid expert assignment.',
        ])
        ->assertSessionHasErrors('assigned_legal_expert_id');

    expect($advisoryRequest->fresh()->assigned_legal_expert_id)->toBeNull();
});

it('prevents duplicate advisory responses after a request is already completed', function (): void {
    $expert = createGuardrailUser(SystemRole::LEGAL_EXPERT, 'completed-expert@ldms.test', 'LEG', 'ADV');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-GUARD-0002',
        'department_id' => Department::query()->where('code', 'HR')->firstOrFail()->id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => createGuardrailUser(SystemRole::DEPARTMENT_REQUESTER, 'completed-requester@ldms.test', 'HR')->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Completed request',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::RESPONDED,
        'workflow_stage' => WorkflowStage::COMPLETED,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => DirectorDecision::APPROVED,
        'description' => 'This request already has a completed response.',
        'date_submitted' => now()->subDays(2)->toDateString(),
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($expert)
        ->post(route('advisory.respond', $advisoryRequest), [
            'response_type' => 'written',
            'summary' => 'Attempting a second response should fail.',
            'advice_text' => 'This is not allowed once the request is completed.',
        ])
        ->assertSessionHasErrors('status');
});

it('prevents closing a case before it reaches an expert-handling stage', function (): void {
    $teamLeader = createGuardrailUser(SystemRole::LITIGATION_TEAM_LEADER, 'case-leader@ldms.test', 'LEG', 'LIT');
    $registrar = createGuardrailUser(SystemRole::REGISTRAR, 'case-registrar@ldms.test', 'LEG', 'ADM');

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-GUARD-0001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'plaintiff' => 'Premature Closure',
        'defendant' => 'Institution',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::PENDING,
        'claim_summary' => 'The case should not be closable before assignment to an expert stage.',
        'filing_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($teamLeader)
        ->patch(route('cases.close', $legalCase), [
            'outcome' => 'Improper closure attempt.',
        ])
        ->assertSessionHasErrors('status');

    expect($legalCase->fresh()->status)->toBe(CaseStatus::UNDER_DIRECTOR_REVIEW);
});

function createGuardrailUser(
    SystemRole $role,
    string $email,
    string $departmentCode,
    ?string $teamCode = null,
): User {
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $team = $teamCode !== null ? Team::query()->where('code', $teamCode)->firstOrFail() : null;

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => $email,
    ]);

    $user->assignRole($role->value);

    return $user;
}

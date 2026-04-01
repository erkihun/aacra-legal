<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\AdvisoryResponse;
use App\Models\Department;
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

it('moves an advisory request through requester director team leader and expert', function (): void {
    $requester = createUserWithRole(SystemRole::DEPARTMENT_REQUESTER, Department::query()->where('code', 'HR')->firstOrFail());
    $director = createUserWithRole(SystemRole::LEGAL_DIRECTOR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $teamLeader = createUserWithRole(SystemRole::ADVISORY_TEAM_LEADER, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADV')->firstOrFail());
    $expert = createUserWithRole(SystemRole::LEGAL_EXPERT, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADV')->firstOrFail());

    $this->actingAs($requester)->post(route('advisory.store'), [
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'subject' => 'Need written advice on procurement review steps',
        'request_type' => 'written',
        'priority' => 'high',
        'description' => 'The procurement department requires legal advice before finalizing a contested bid evaluation.',
        'due_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();

    $advisoryRequest = AdvisoryRequest::query()->firstOrFail();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW);

    $this->actingAs($director)->patch(route('advisory.review', $advisoryRequest), [
        'director_decision' => 'approved',
        'director_notes' => 'Proceed with advisory team leader review.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER);
    expect($advisoryRequest->assigned_team_leader_id)->toBe($teamLeader->id);

    $this->actingAs($teamLeader)->patch(route('advisory.assign', $advisoryRequest), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Prepare written opinion.',
    ])->assertSessionHasNoErrors();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::ASSIGNED_TO_EXPERT);
    expect($advisoryRequest->assigned_legal_expert_id)->toBe($expert->id);

    $this->actingAs($expert)->post(route('advisory.respond', $advisoryRequest), [
        'subject' => 'Written opinion on procurement review steps',
        'response' => 'Keep the bid-evaluation memo, bidder communication record, and appeal timeline on file.',
    ])->assertRedirect(route('advisory.show', $advisoryRequest))
        ->assertSessionHasNoErrors();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::RESPONDED);
    expect($advisoryRequest->responses)->toHaveCount(1);
});

it('provides the advisory id to the advisory show page props', function (): void {
    $director = createUserWithRole(
        SystemRole::LEGAL_DIRECTOR,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADM')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Director review payload check',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => 'director',
        'priority' => 'medium',
        'director_decision' => 'pending',
        'description' => 'Ensure the Inertia show payload exposes the advisory id for Ziggy route generation.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('requestItem.id', $advisoryRequest->id)
        );
});

it('hides advisory assignment workspace forms once assignment already exists', function (): void {
    $director = createUserWithRole(
        SystemRole::LEGAL_DIRECTOR,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADM')->firstOrFail(),
    );

    $teamLeader = createUserWithRole(
        SystemRole::ADVISORY_TEAM_LEADER,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $expert = createUserWithRole(
        SystemRole::LEGAL_EXPERT,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $teamLeaderAssignedRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9002',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'subject' => 'Assigned to team leader',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => 'team_leader',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Director assignment should stay hidden once a team leader is already assigned.',
        'date_submitted' => now()->toDateString(),
    ]);

    $expertAssignedRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9003',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Assigned to expert',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Expert assignment should stay hidden once an expert is already assigned.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('advisory.show', $teamLeaderAssignedRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('workspace.canAssignTeamLeader', false)
            ->where('workspace.canAssignExpert', false)
        );

    $this->actingAs($teamLeader)
        ->get(route('advisory.show', $expertAssignedRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('workspace.canAssignTeamLeader', false)
            ->where('workspace.canAssignExpert', false)
        );
});

it('renders the advisory response create page for the assigned expert', function (): void {
    $teamLeader = createUserWithRole(
        SystemRole::ADVISORY_TEAM_LEADER,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $expert = createUserWithRole(
        SystemRole::LEGAL_EXPERT,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9010',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Response create page',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'The assigned expert should be able to open the dedicated response create page.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($expert)
        ->get(route('advisory.responses.create', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Responses/Create')
            ->where('requestItem.id', $advisoryRequest->id)
            ->where('requestItem.request_number', $advisoryRequest->request_number)
        );
});

it('shows advisory request delete availability on the list and allows deleting an eligible request', function (): void {
    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9020',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Delete eligible advisory request',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::RETURNED,
        'workflow_stage' => 'requester',
        'priority' => 'medium',
        'director_decision' => 'returned',
        'description' => 'This returned request should be deletable by the original requester.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($requester)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->where('requests.data.0.id', $advisoryRequest->id)
            ->where('requests.data.0.can_delete', true)
        );

    $this->actingAs($requester)
        ->delete(route('advisory.destroy', $advisoryRequest))
        ->assertRedirect();

    $this->assertSoftDeleted('advisory_requests', [
        'id' => $advisoryRequest->id,
    ]);
});

it('shows advisory request edit availability on the list for requester-owned unassigned requests', function (): void {
    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9020A',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Editable advisory request',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => 'director',
        'priority' => 'medium',
        'director_decision' => 'pending',
        'description' => 'This unassigned request should expose the edit action on the requester list.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($requester)
        ->get(route('advisory.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->where('requests.data.0.id', $advisoryRequest->id)
            ->where('requests.data.0.can_update', true)
        );
});

it('renders the advisory response edit page and updates the response for the original responder', function (): void {
    $teamLeader = createUserWithRole(
        SystemRole::ADVISORY_TEAM_LEADER,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $expert = createUserWithRole(
        SystemRole::LEGAL_EXPERT,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9021',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Editable advisory response',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::RESPONDED,
        'workflow_stage' => 'completed',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'The original responder should be able to edit the saved response.',
        'date_submitted' => now()->toDateString(),
        'completed_at' => now(),
    ]);

    $response = $advisoryRequest->responses()->create([
        'responder_id' => $expert->id,
        'subject' => 'Original subject',
        'response' => '<p>Original response body.</p>',
        'summary' => 'Original subject',
        'advice_text' => '<p>Original response body.</p>',
        'responded_at' => now(),
    ]);

    $this->actingAs($expert)
        ->get(route('advisory.responses.edit', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Responses/Create')
            ->where('mode', 'edit')
            ->where('responseItem.id', $response->id)
        );

    $this->actingAs($expert)
        ->patch(route('advisory.responses.update', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]), [
            'subject' => 'Updated subject',
            'response' => '<p>Updated response body.</p>',
        ])
        ->assertRedirect(route('advisory.responses.show', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertSessionHasNoErrors();

    $response->refresh();
    $advisoryRequest->refresh();

    expect($response->subject)->toBe('Updated subject');
    expect($response->response)->toBe('<p>Updated response body.</p>');
    expect($advisoryRequest->internal_summary)->toBe('Updated subject');
});

it('shows advisory response edit and delete capabilities and allows deleting a response', function (): void {
    $teamLeader = createUserWithRole(
        SystemRole::ADVISORY_TEAM_LEADER,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $expert = createUserWithRole(
        SystemRole::LEGAL_EXPERT,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9022',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Deletable advisory response',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::RESPONDED,
        'workflow_stage' => 'completed',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'The original responder should be able to delete the response and reopen the request.',
        'date_submitted' => now()->toDateString(),
        'completed_at' => now(),
    ]);

    $response = $advisoryRequest->responses()->create([
        'responder_id' => $expert->id,
        'subject' => 'Response subject',
        'response' => '<p>Response body.</p>',
        'summary' => 'Response subject',
        'advice_text' => '<p>Response body.</p>',
        'responded_at' => now(),
    ]);

    $this->actingAs($expert)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('requestItem.responses.0.id', $response->id)
            ->where('requestItem.responses.0.can_update', true)
            ->where('requestItem.responses.0.can_delete', true)
        );

    $this->actingAs($expert)
        ->delete(route('advisory.responses.destroy', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertRedirect(route('advisory.show', $advisoryRequest))
        ->assertSessionHasNoErrors();

    expect(AdvisoryResponse::query()->whereKey($response->id)->exists())->toBeFalse();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::ASSIGNED_TO_EXPERT);
    expect($advisoryRequest->completed_at)->toBeNull();
});

function createUserWithRole(SystemRole $role, Department $department, ?Team $team = null): User
{
    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\AdvisoryResponse;
use App\Models\Department;
use App\Models\LetterTemplate;
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

it('renders template-backed formal letter data on the internal advisory show page', function (): void {
    $director = createUserWithRole(
        SystemRole::LEGAL_DIRECTOR,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADM')->firstOrFail(),
    );

    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );

    $template = LetterTemplate::factory()->create([
        'is_active' => true,
        'is_default' => true,
        'name' => 'Requester Default Template',
    ]);

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9001A',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'letter_template_id' => $template->id,
        'letter_snapshot' => [
            'template_id' => (string) $template->id,
            'template_name' => $template->name,
            'language' => $template->language,
            'header_image_path' => null,
            'footer_image_path' => null,
            'salutation_template' => '<p>To whom it may concern,</p>',
            'body_content' => '<p>Formal request body</p>',
            'closing_content' => '<p>Sincerely,</p>',
            'layout_config' => null,
        ],
        'subject' => 'Template-backed advisory request',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => 'director',
        'priority' => 'medium',
        'director_decision' => 'pending',
        'description' => '<p>Internal review should see this formal body.</p>',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('requestItem.formal_letter.template_name', 'Requester Default Template')
            ->where('requestItem.formal_letter.body_content', '<p>Internal review should see this formal body.</p>')
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

it('stores new advisory responses as pending approval', function (): void {
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

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9010A',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Pending response approval',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'A newly recorded response should wait for approval before the requester can see it.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($expert)
        ->post(route('advisory.respond', $advisoryRequest), [
            'subject' => 'Pending response subject',
            'response' => '<p>Pending response body.</p>',
        ])
        ->assertSessionHasNoErrors();

    $response = $advisoryRequest->fresh()->responses()->firstOrFail();

    expect($response->approval_status)->toBe('pending')
        ->and($response->approved_by)->toBeNull()
        ->and($response->approved_at)->toBeNull();

    $this->actingAs($requester)
        ->get(route('advisory.responses.show', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertForbidden();

    $this->actingAs($director)
        ->patch(route('advisory.responses.approve', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertSessionHasNoErrors();

    $response->refresh();

    expect($response->approval_status)->toBe('approved')
        ->and($response->approved_by)->toBe($director->id)
        ->and($response->approved_at)->not->toBeNull();

    $this->actingAs($requester)
        ->get(route('advisory.responses.show', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertOk();

    $this->actingAs($requester)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->has('requestItem.responses', 1)
            ->where('requestItem.responses.0.id', $response->id)
        );
});

it('allows users with the advisory response comment permission to comment on visible advisory responses', function (): void {
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

    $director->givePermissionTo(['comments.create', 'advisory-responses.comment']);

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9010B',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Response comments',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::RESPONDED,
        'workflow_stage' => 'completed',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Authorized users should be able to comment on advisory responses.',
        'date_submitted' => now()->toDateString(),
        'completed_at' => now(),
    ]);

    $response = $advisoryRequest->responses()->create([
        'responder_id' => $expert->id,
        'subject' => 'Approved response',
        'response' => '<p>Approved response body.</p>',
        'summary' => 'Approved response',
        'advice_text' => '<p>Approved response body.</p>',
        'responded_at' => now(),
        'approval_status' => 'approved',
        'approved_by' => $director->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($director)
        ->post(route('advisory.responses.comments.store', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]), [
            'body' => 'Reviewed and acknowledged.',
        ])
        ->assertSessionHasNoErrors();

    expect($response->fresh()->comments()->count())->toBe(1);

    $this->actingAs($director)
        ->get(route('advisory.responses.show', [
            'advisoryRequest' => $advisoryRequest,
            'advisoryResponse' => $response,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Responses/Show')
            ->where('responseItem.can_comment', true)
            ->where('responseItem.comments.0.body', 'Reviewed and acknowledged.')
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

it('runs verbal advisory through the normal advisory assignment and response workflow', function (): void {
    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
    );
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

    $this->actingAs($requester)->post(route('advisory.store'), [
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'subject' => 'Need immediate verbal advisory',
        'request_type' => 'verbal',
        'priority' => 'high',
        'description' => 'A verbal advisory request should still go through the normal workflow.',
        'due_date' => now()->addDay()->toDateString(),
    ])->assertRedirect();

    $advisoryRequest = AdvisoryRequest::query()->where('request_type', 'verbal')->firstOrFail();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW);
    expect($advisoryRequest->workflow_stage)->toBe(\App\Enums\WorkflowStage::DIRECTOR);

    $this->actingAs($director)->patch(route('advisory.review', $advisoryRequest), [
        'director_decision' => 'approved',
        'director_notes' => 'Route the verbal advisory through the advisory team.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($teamLeader)->patch(route('advisory.assign', $advisoryRequest), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Respond as verbal advisory.',
    ])->assertSessionHasNoErrors();

    $this->actingAs($expert)->post(route('advisory.respond', $advisoryRequest), [
        'subject' => 'Verbal advisory response',
        'response' => '<p>Verbal advisory response recorded through the regular advisory response path.</p>',
    ])->assertRedirect(route('advisory.show', $advisoryRequest))
        ->assertSessionHasNoErrors();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::RESPONDED);
    expect($advisoryRequest->request_type->value)->toBe('verbal');

    $this->actingAs($expert)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->where('requestItem.request_type', 'verbal')
            ->where('requestItem.responses.0.subject', 'Verbal advisory response')
        );
});

it('denies verbal advisory creation to users without advisory create permission', function (): void {
    $user = User::factory()->create([
        'department_id' => Department::query()->where('code', 'HR')->firstOrFail()->id,
    ]);

    $this->actingAs($user)->get(route('advisory.create'))->assertForbidden();

    $this->actingAs($user)->post(route('advisory.store'), [
        'department_id' => $user->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'subject' => 'Unauthorized verbal advisory',
        'request_type' => 'verbal',
        'priority' => 'medium',
        'description' => 'This should be rejected.',
    ])->assertForbidden();
});

it('denies verbal advisory responses to users outside the authorized advisory response scope', function (): void {
    $requester = createUserWithRole(
        SystemRole::DEPARTMENT_REQUESTER,
        Department::query()->where('code', 'HR')->firstOrFail(),
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
    $otherUser = createUserWithRole(
        SystemRole::LEGAL_EXPERT,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADV')->firstOrFail(),
    );

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-VERBAL-DENY-001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Protected verbal advisory response',
        'request_type' => 'verbal',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Only the assigned expert should be able to respond.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($otherUser)->post(route('advisory.respond', $advisoryRequest), [
        'subject' => 'Unauthorized response',
        'response' => '<p>This response should be blocked.</p>',
    ])->assertForbidden();

    expect($advisoryRequest->responses()->count())->toBe(0);
});

function createUserWithRole(SystemRole $role, Department $department, ?Team $team = null): User
{
    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);
    syncTestTeamLeadership($user, $team, $role);

    return $user;
}

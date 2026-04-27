<?php

declare(strict_types=1);

use App\Enums\ComplaintCommitteeOutcome;
use App\Enums\ComplaintEscalationType;
use App\Enums\ComplaintStatus;
use App\Enums\SystemRole;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use App\Notifications\ComplaintAssignedToDepartmentNotification;
use App\Notifications\ComplaintCommitteeDecisionIssuedNotification;
use App\Notifications\ComplaintDepartmentResponseRecordedNotification;
use App\Notifications\ComplaintEscalatedNotification;
use App\Notifications\ComplaintSubmittedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Cache::flush();
    Storage::fake('public');
    Notification::fake();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('loads the complaint create page for authorized complainants', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');

    $this->actingAs($complainant)
        ->get(route('complaints.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('derivedComplainantType', 'branch_employee')
            ->has('branches')
            ->has('departments')
            ->where('authUser.name', $complainant->name)
        );
});

it('allows a complainant to create a complaint and route it to the selected department', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $branch = Branch::query()->firstOrFail();
    $department = Department::query()->where('code', 'LEG')->firstOrFail();

    $this->actingAs($complainant)
        ->post(route('complaints.store'), validComplaintSubmissionData($branch->id, $department->id))
        ->assertRedirect();

    $complaint = Complaint::query()->firstOrFail();

    expect($complaint->department_id)->toBe($department->id)
        ->and($complaint->branch_id)->toBe($branch->id)
        ->and($complaint->complainant_name)->toBe('የቅሬታ አቅራቢ ሙሉ ስም')
        ->and($complaint->complainant_city)->toBe('Addis Ababa')
        ->and($complaint->complaint_essence)->toContain('የቅሬታው ፍሬ ሀሳብ')
        ->and($complaint->requested_resolution)->toContain('በአጭሩ ግለጹ')
        ->and($complaint->status)->toBe(ComplaintStatus::ASSIGNED_TO_DEPARTMENT)
        ->and($complaint->attachments()->count())->toBe(1);

    Notification::assertSentToTimes($complainant, ComplaintSubmittedNotification::class, 1);
    Notification::assertSentToTimes($departmentUser, ComplaintAssignedToDepartmentNotification::class, 1);
});

it('validates required institutional complaint fields and preserves submitted values', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $branch = Branch::query()->firstOrFail();

    $this->actingAs($complainant)
        ->from(route('complaints.create'))
        ->post(route('complaints.store'), [
            'complainant_name' => 'Existing Input',
            'complainant_phone' => '',
            'complainant_city' => 'Addis Ababa',
            'branch_id' => $branch->id,
            'department_id' => '',
            'complaint_essence' => '',
            'incident_date' => '',
            'requested_resolution' => '',
        ])
        ->assertRedirect(route('complaints.create'))
        ->assertSessionHasErrors([
            'complainant_phone',
            'department_id',
            'complaint_essence',
            'incident_date',
            'requested_resolution',
        ])
        ->assertSessionHasInput([
            'complainant_name' => 'Existing Input',
            'complainant_city' => 'Addis Ababa',
        ]);
});

it('shows complaint index rows according to role visibility', function (): void {
    $owner = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $otherOwner = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'PROC');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $committeeUser = createComplaintUser(SystemRole::COMPLAINT_COMMITTEE, 'LEG');

    $visibleComplaint = complaintFixture($owner, 'LEG', [
        'subject' => 'Visible to owner and department',
    ]);
    $hiddenComplaint = complaintFixture($otherOwner, 'PROC', [
        'subject' => 'Only procurement should see this',
    ]);
    $escalatedComplaint = complaintFixture($owner, 'LEG', [
        'subject' => 'Escalated matter',
        'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
        'is_escalated' => true,
        'forwarded_to_committee_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Index')
            ->has('complaints.data', 2)
            ->where('complaints.data.0.complainant_name', $owner->name)
        );

    $this->actingAs($departmentUser)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertSee($visibleComplaint->complaint_number)
        ->assertDontSee($hiddenComplaint->complaint_number);

    $this->actingAs($committeeUser)
        ->get(route('complaints.index'))
        ->assertOk()
        ->assertSee($escalatedComplaint->complaint_number)
        ->assertDontSee($hiddenComplaint->complaint_number);
});

it('loads the complaint show page with complaint details', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $complaint = complaintFixture($complainant, 'LEG', [
        'complainant_city' => 'Addis Ababa',
        'complainant_sub_city' => 'Bole',
        'complainant_woreda' => '08',
        'complainant_house_number' => 'H-201',
        'complaint_essence' => 'Structured complaint essence for the grievance form.',
        'incident_date' => now()->subDays(3)->toDateString(),
        'incident_sub_city' => 'Kirkos',
        'incident_woreda' => '05',
        'concerned_employee_name' => 'Case worker name',
        'evidence_note' => 'Photocopy of service request',
        'requested_resolution' => 'Provide written corrective action.',
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Show')
            ->where('complaintItem.id', $complaint->id)
            ->where('complaintItem.complaint_number', $complaint->complaint_number)
            ->where('complaintItem.concerned_employee_name', 'Case worker name')
            ->where('complaintItem.requested_resolution', 'Provide written corrective action.')
        );
});

it('loads the complaint edit page with existing institutional intake values', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $complaint = complaintFixture($complainant, 'LEG', [
        'complainant_city' => 'Addis Ababa',
        'complainant_sub_city' => 'Yeka',
        'complaint_essence' => 'Existing complaint essence for edit mode.',
        'requested_resolution' => 'Requested remedy text.',
        'concerned_employee_name' => 'Employee on record',
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.edit', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('mode', 'edit')
            ->where('complaintItem.complainant_city', 'Addis Ababa')
            ->where('complaintItem.concerned_employee_name', 'Employee on record')
            ->where('complaintItem.requested_resolution', 'Requested remedy text.')
        );
});

it('allows the complaint owner to update institutional intake fields', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $department = Department::query()->where('code', 'LEG')->firstOrFail();
    $branch = Branch::query()->firstOrFail();
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($complainant)
        ->patch(route('complaints.update', $complaint), [
            'complainant_name' => 'Updated Complainant Name',
            'complainant_city' => 'Addis Ababa',
            'complainant_sub_city' => 'Lideta',
            'complainant_woreda' => '04',
            'complainant_house_number' => 'H-222',
            'complainant_phone' => '+251911000111',
            'complaint_essence' => 'Updated institutional complaint essence for the revised intake form.',
            'incident_date' => now()->subDay()->toDateString(),
            'branch_id' => $branch->id,
            'incident_sub_city' => 'Lideta',
            'incident_woreda' => '04',
            'department_id' => $department->id,
            'concerned_employee_name' => 'Updated employee',
            'evidence_note' => 'Updated evidence note.',
            'requested_resolution' => 'Updated requested remedy.',
        ])
        ->assertRedirect(route('complaints.show', $complaint));

    $complaint->refresh();

    expect($complaint->complainant_name)->toBe('Updated Complainant Name')
        ->and($complaint->complainant_sub_city)->toBe('Lideta')
        ->and($complaint->concerned_employee_name)->toBe('Updated employee')
        ->and($complaint->requested_resolution)->toBe('Updated requested remedy.');
});

it('denies unauthorized users from editing complaint intake fields', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $outsider = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'PROC');
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($outsider)
        ->get(route('complaints.edit', $complaint))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('complaints.update', $complaint), validComplaintSubmissionData($complaint->branch_id, $complaint->department_id))
        ->assertForbidden();
});

it('renders the complaint print view without error', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $complaint = complaintFixture($complainant, 'LEG', [
        'complaint_essence' => 'Printable complaint essence.',
        'requested_resolution' => 'Printable requested resolution.',
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.print', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Print')
            ->where('complaintItem.complaint_number', $complaint->complaint_number)
        );
});

it('loads older complaints safely when the new intake fields are empty', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $complaint = complaintFixture($complainant, 'LEG', [
        'complaint_essence' => null,
        'incident_date' => null,
        'incident_sub_city' => null,
        'incident_woreda' => null,
        'requested_resolution' => null,
        'evidence_note' => null,
        'concerned_employee_name' => null,
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Show')
            ->where('complaintItem.complaint_number', $complaint->complaint_number)
        );
});

it('allows an authorized department user to submit one response and notifies the complainant once', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($departmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Department response',
            'response_content' => '<p>The department response explains the decision.</p>',
            'attachments' => [UploadedFile::fake()->create('response.pdf', 12, 'application/pdf')],
        ])
        ->assertSessionHasNoErrors();

    $complaint->refresh();

    expect($complaint->status)->toBe(ComplaintStatus::DEPARTMENT_RESPONDED)
        ->and($complaint->responses()->count())->toBe(1)
        ->and($complaint->responses()->firstOrFail()->attachments()->count())->toBe(1);

    Notification::assertSentToTimes($complainant, ComplaintDepartmentResponseRecordedNotification::class, 1);

    $this->actingAs($departmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Duplicate response',
            'response_content' => '<p>This should fail because one response is allowed.</p>',
        ])
        ->assertSessionHasErrors('response_content');
});

it('allows the complainant to forward dissatisfaction to the committee only once', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $committeeUser = createComplaintUser(SystemRole::COMPLAINT_COMMITTEE, 'LEG');
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($departmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Department response',
            'response_content' => '<p>The department responded to the complaint.</p>',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($complainant)
        ->post(route('complaints.forward', $complaint), [
            'dissatisfaction_reason' => 'The department response does not address the key issue.',
        ])
        ->assertSessionHasNoErrors();

    $complaint->refresh();

    expect($complaint->status)->toBe(ComplaintStatus::ESCALATED_TO_COMMITTEE)
        ->and($complaint->is_escalated)->toBeTrue()
        ->and($complaint->escalations()->count())->toBe(1);

    Notification::assertSentToTimes($committeeUser, ComplaintEscalatedNotification::class, 1);

    $this->actingAs($complainant)
        ->post(route('complaints.forward', $complaint), [
            'dissatisfaction_reason' => 'Trying to forward again.',
        ])
        ->assertForbidden();
});

it('allows a committee user to record one final decision and notify the complainant and department', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $committeeUser = createComplaintUser(SystemRole::COMPLAINT_COMMITTEE, 'LEG');
    $complaint = complaintFixture($complainant, 'LEG', [
        'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
        'is_escalated' => true,
        'forwarded_to_committee_at' => now(),
        'is_dissatisfied' => true,
    ]);

    $this->actingAs($committeeUser)
        ->post(route('complaints.decide', $complaint), [
            'investigation_notes' => '<p>Committee investigated the complaint thoroughly.</p>',
            'decision_summary' => 'Committee final decision',
            'decision_detail' => '<p>The complaint is upheld with corrective action.</p>',
            'outcome' => ComplaintCommitteeOutcome::UPHELD->value,
            'attachments' => [UploadedFile::fake()->create('decision.pdf', 18, 'application/pdf')],
        ])
        ->assertSessionHasNoErrors();

    $complaint->refresh();

    expect($complaint->status)->toBe(ComplaintStatus::RESOLVED)
        ->and($complaint->committeeDecisions()->count())->toBe(1);

    Notification::assertSentToTimes($complainant, ComplaintCommitteeDecisionIssuedNotification::class, 1);
    Notification::assertSentToTimes($departmentUser, ComplaintCommitteeDecisionIssuedNotification::class, 1);

    $this->actingAs($committeeUser)
        ->post(route('complaints.decide', $complaint), [
            'decision_summary' => 'Duplicate decision',
            'decision_detail' => '<p>This should be rejected.</p>',
            'outcome' => ComplaintCommitteeOutcome::REJECTED->value,
        ])
        ->assertSessionHasErrors('decision_detail');
});

it('saves complaint settings for authorized admins and loads the reports page', function (): void {
    $admin = createComplaintUser(SystemRole::SUPER_ADMIN, 'LEG');

    $this->actingAs($admin)
        ->put(route('complaints.settings.update'), [
            'default_response_deadline_days' => 7,
            'auto_escalation_enabled' => true,
            'reminder_interval_hours' => 12,
            'committee_notification_user_ids' => [],
            'allow_client_self_registration' => true,
            'complaint_code_prefix' => 'CMP',
            'allowed_attachment_types' => ['pdf', 'docx'],
            'max_attachment_size_mb' => 8,
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get(route('complaints.reports'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Reports/Index')
            ->has('metrics')
            ->has('by_complainant_type')
        );
});

it('denies unauthorized users from department response actions', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $outsider = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'PROC');
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($outsider)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Unauthorized',
            'response_content' => '<p>Should not be allowed.</p>',
        ])
        ->assertForbidden();
});

it('uploads complaint attachments and records timeline history for key actions', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($complainant)
        ->post(route('complaints.attachments.store', $complaint), [
            'attachments' => [UploadedFile::fake()->create('supporting-note.pdf', 16, 'application/pdf')],
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($departmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Department response',
            'response_content' => '<p>The department recorded a response.</p>',
        ])
        ->assertSessionHasNoErrors();

    expect($complaint->fresh()->attachments()->count())->toBe(1)
        ->and(Attachment::query()->count())->toBeGreaterThan(0)
        ->and($complaint->fresh()->histories()->count())->toBeGreaterThanOrEqual(3);
});

it('auto escalates an overdue complaint once without duplicate escalation records', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $committeeUser = createComplaintUser(SystemRole::COMPLAINT_COMMITTEE, 'LEG');
    $complaint = complaintFixture($complainant, 'LEG', [
        'department_response_deadline_at' => now()->subDay(),
        'status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
    ]);

    $action = app(\App\Actions\AutoEscalateComplaintAction::class);

    $action->execute($complaint);
    $action->execute($complaint->fresh());

    $complaint->refresh();

    expect($complaint->status)->toBe(ComplaintStatus::ESCALATED_TO_COMMITTEE)
        ->and($complaint->escalations()->where('escalation_type', ComplaintEscalationType::AUTO)->count())->toBe(1);

    Notification::assertSentToTimes($committeeUser, ComplaintEscalatedNotification::class, 1);
});

function createComplaintUser(SystemRole $role, string $departmentCode): User
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $branch = Branch::query()->firstOrFail();

    $user = User::factory()->create([
        'department_id' => $department->id,
        'branch_id' => $role === SystemRole::COMPLAINT_CLIENT ? null : $branch->id,
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+2519'.str_pad((string) fake()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
    ]);

    $user->assignRole($role->value);

    return $user;
}

function validComplaintSubmissionData(string $branchId, string $departmentId): array
{
    return [
        'complainant_name' => 'የቅሬታ አቅራቢ ሙሉ ስም',
        'complainant_city' => 'Addis Ababa',
        'complainant_sub_city' => 'Bole',
        'complainant_woreda' => '09',
        'complainant_house_number' => 'H-145',
        'complainant_phone' => '+251911223344',
        'complaint_essence' => 'የቅሬታው ፍሬ ሀሳብ ወይም ጥቆማ በበቂ ሁኔታ ተገልጿል።',
        'incident_date' => now()->subDays(2)->toDateString(),
        'branch_id' => $branchId,
        'incident_sub_city' => 'Kirkos',
        'incident_woreda' => '03',
        'department_id' => $departmentId,
        'concerned_employee_name' => 'Concerned service provider',
        'evidence_note' => 'Photocopy of supporting evidence attached.',
        'requested_resolution' => 'በአጭሩ ግለጹ የተፈለገው መፍትሄ እንዲሰጥ።',
        'attachments' => [UploadedFile::fake()->create('complaint.pdf', 20, 'application/pdf')],
    ];
}

function complaintFixture(User $complainant, string $departmentCode, array $overrides = []): Complaint
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $complainantType = $complainant->hasRole(SystemRole::COMPLAINT_CLIENT->value)
        ? 'client'
        : ($complainant->branch_id ? 'branch_employee' : 'head_office_employee');
    $submittedAt = now();

    $complaint = Complaint::query()->create(array_merge([
        'complaint_number' => 'CMP-'.fake()->unique()->numerify('2026-####'),
        'complainant_user_id' => $complainant->id,
        'branch_id' => $complainant->branch_id,
        'department_id' => $department->id,
        'complainant_type' => $complainantType,
        'complainant_name' => $complainant->name,
        'complainant_email' => $complainant->email,
        'complainant_phone' => $complainant->phone,
        'complainant_city' => null,
        'complainant_sub_city' => null,
        'complainant_woreda' => null,
        'complainant_house_number' => null,
        'subject' => 'Complaint workflow test matter',
        'complaint_essence' => 'Structured complaint essence for the workflow tests.',
        'details' => '<p>This complaint contains enough detail for the test workflow.</p>',
        'category' => 'General',
        'evidence_note' => null,
        'requested_resolution' => 'Workflow test requested resolution.',
        'priority' => 'medium',
        'incident_date' => now()->subDay()->toDateString(),
        'incident_sub_city' => null,
        'incident_woreda' => null,
        'concerned_employee_name' => null,
        'submitted_at' => $submittedAt,
        'department_response_deadline_at' => now()->addDays(5),
        'status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
    ], $overrides));

    $complaint->histories()->createMany([
        [
            'actor_id' => $complainant->id,
            'from_status' => null,
            'to_status' => ComplaintStatus::SUBMITTED,
            'action' => 'submitted',
            'notes' => 'Complaint submitted by complainant.',
            'acted_at' => $submittedAt,
        ],
        [
            'actor_id' => $complainant->id,
            'from_status' => ComplaintStatus::SUBMITTED,
            'to_status' => $complaint->status,
            'action' => 'assigned_to_department',
            'notes' => 'Complaint routed to the responsible department.',
            'acted_at' => $submittedAt,
        ],
    ]);

    return $complaint;
}

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
        );
});

it('allows a complainant to create a complaint and route it to the selected department', function (): void {
    $complainant = createComplaintUser(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $departmentUser = createComplaintUser(SystemRole::LEGAL_EXPERT, 'LEG');
    $branch = Branch::query()->firstOrFail();
    $department = Department::query()->where('code', 'LEG')->firstOrFail();

    $this->actingAs($complainant)
        ->post(route('complaints.store'), [
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'subject' => 'Procurement complaint routing',
            'details' => '<p>The complaint details are long enough for validation.</p>',
            'category' => 'Procurement',
            'priority' => 'high',
            'attachments' => [UploadedFile::fake()->create('complaint.pdf', 20, 'application/pdf')],
        ])
        ->assertRedirect();

    $complaint = Complaint::query()->firstOrFail();

    expect($complaint->department_id)->toBe($department->id)
        ->and($complaint->branch_id)->toBe($branch->id)
        ->and($complaint->status)->toBe(ComplaintStatus::ASSIGNED_TO_DEPARTMENT)
        ->and($complaint->attachments()->count())->toBe(1);

    Notification::assertSentToTimes($complainant, ComplaintSubmittedNotification::class, 1);
    Notification::assertSentToTimes($departmentUser, ComplaintAssignedToDepartmentNotification::class, 1);
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
    $complaint = complaintFixture($complainant, 'LEG');

    $this->actingAs($complainant)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Show')
            ->where('complaintItem.id', $complaint->id)
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
        'subject' => 'Complaint workflow test matter',
        'details' => '<p>This complaint contains enough detail for the test workflow.</p>',
        'category' => 'General',
        'priority' => 'medium',
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

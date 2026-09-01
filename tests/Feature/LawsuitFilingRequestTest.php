<?php

declare(strict_types=1);

use App\Enums\LawsuitRequestStatus;
use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\LetterTemplate;
use App\Models\LawsuitFilingRequest;
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

// ────────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────────

function lsrCreateUserWithRole(SystemRole $role, Department $department): User
{
    $user = User::factory()->create([
        'department_id' => $department->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

function hrDepartment(): Department
{
    return Department::query()->where('code', 'HR')->firstOrFail();
}

function legDepartment(): Department
{
    return Department::query()->where('code', 'LEG')->firstOrFail();
}

// ────────────────────────────────────────────────────────────────
// Submit (store)
// ────────────────────────────────────────────────────────────────

it('allows a department requester to submit a lawsuit filing request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $this->actingAs($requester)
        ->post(route('lawsuit-requests.store'), [
            'requesting_department_id' => $requester->department_id,
            'subject' => 'Contract dispute with vendor',
            'description' => 'We need legal assistance to resolve a payment dispute with Vendor X.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lawsuitRequest = LawsuitFilingRequest::query()->firstOrFail();

    expect($lawsuitRequest->status)->toBe(LawsuitRequestStatus::SUBMITTED);
    expect($lawsuitRequest->subject)->toBe('Contract dispute with vendor');
    expect($lawsuitRequest->created_by)->toBe($requester->id);
    expect($lawsuitRequest->request_code)->toStartWith('LSR-');
});

it('rejects a store request without a subject', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $this->actingAs($requester)
        ->post(route('lawsuit-requests.store'), [
            'requesting_department_id' => $requester->department_id,
            'description' => 'Missing subject field.',
        ])
        ->assertSessionHasErrors('subject');
});

it('blocks unauthenticated users from submitting a request', function (): void {
    $this->post(route('lawsuit-requests.store'), [
        'requesting_department_id' => hrDepartment()->id,
        'subject' => 'test',
        'description' => 'test',
    ])->assertRedirect(route('login'));
});

// ────────────────────────────────────────────────────────────────
// Index
// ────────────────────────────────────────────────────────────────

it('shows the requester only their own requests on the index page', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());
    $other = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'subject' => 'My request',
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    LawsuitFilingRequest::factory()->create([
        'created_by' => $other->id,
        'requesting_department_id' => $other->department_id,
        'subject' => 'Someone else request',
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($requester)
        ->get(route('lawsuit-requests.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LawsuitRequests/Index')
            ->where('requests.data', fn ($data) => count($data) === 1 && $data[0]['subject'] === 'My request')
        );
});

it('shows reviewers all requests on the index page', function (): void {
    $reviewer = lsrCreateUserWithRole(SystemRole::LEGAL_DIRECTOR, legDepartment());
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    LawsuitFilingRequest::factory()->count(3)->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($reviewer)
        ->get(route('lawsuit-requests.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LawsuitRequests/Index')
            ->where('requests.data', fn ($data) => count($data) === 3)
        );
});

// ────────────────────────────────────────────────────────────────
// Show
// ────────────────────────────────────────────────────────────────

it('shows the request detail to the creator', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($requester)
        ->get(route('lawsuit-requests.show', $lawsuitRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LawsuitRequests/Show')
            ->has('requestItem')
        );
});

it('renders template-backed formal letter data on the internal lawsuit show page', function (): void {
    $reviewer = lsrCreateUserWithRole(SystemRole::LEGAL_DIRECTOR, legDepartment());
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $template = LetterTemplate::factory()->create([
        'is_active' => true,
        'is_default' => true,
        'name' => 'Requester Default Template',
    ]);

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requester_account_id' => null,
        'requesting_department_id' => $requester->department_id,
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
        'subject' => 'Template-backed lawsuit request',
        'description' => '<p>Internal review should see this formal body.</p>',
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($reviewer)
        ->get(route('lawsuit-requests.show', $lawsuitRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LawsuitRequests/Show')
            ->where('requestItem.formal_letter.template_name', 'Requester Default Template')
            ->where('requestItem.formal_letter.body_content', '<p>Internal review should see this formal body.</p>')
        );
});

it('denies show access to a user who did not create the request', function (): void {
    $owner = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());
    $other = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $owner->id,
        'requesting_department_id' => $owner->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($other)
        ->get(route('lawsuit-requests.show', $lawsuitRequest))
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Update
// ────────────────────────────────────────────────────────────────

it('allows the creator to update a submitted request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($requester)
        ->patch(route('lawsuit-requests.update', $lawsuitRequest), [
            'requesting_department_id' => $requester->department_id,
            'subject' => 'Updated subject',
            'description' => 'Updated description with more detail.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($lawsuitRequest->fresh()->subject)->toBe('Updated subject');
});

it('blocks updating a request that is approved', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::APPROVED,
    ]);

    $this->actingAs($requester)
        ->patch(route('lawsuit-requests.update', $lawsuitRequest), [
            'requesting_department_id' => $requester->department_id,
            'subject' => 'Should be blocked',
            'description' => 'Should be blocked.',
        ])
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Review
// ────────────────────────────────────────────────────────────────

it('allows a reviewer to approve a submitted request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());
    $reviewer = lsrCreateUserWithRole(SystemRole::LEGAL_DIRECTOR, legDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($reviewer)
        ->patch(route('lawsuit-requests.review', $lawsuitRequest), [
            'status' => 'approved',
            'reviewer_notes' => 'Approved for legal action.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lawsuitRequest->refresh();

    expect($lawsuitRequest->status)->toBe(LawsuitRequestStatus::APPROVED);
    expect($lawsuitRequest->reviewed_by)->toBe($reviewer->id);
    expect($lawsuitRequest->reviewer_notes)->toBe('Approved for legal action.');
});

it('allows a reviewer to return a request with notes', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());
    $reviewer = lsrCreateUserWithRole(SystemRole::LEGAL_DIRECTOR, legDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($reviewer)
        ->patch(route('lawsuit-requests.review', $lawsuitRequest), [
            'status' => 'returned',
            'reviewer_notes' => 'Please provide additional documentation.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($lawsuitRequest->fresh()->status)->toBe(LawsuitRequestStatus::RETURNED);
});

it('blocks re-reviewing an already approved request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());
    $reviewer = lsrCreateUserWithRole(SystemRole::LEGAL_DIRECTOR, legDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::APPROVED,
        'reviewed_by' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)
        ->patch(route('lawsuit-requests.review', $lawsuitRequest), [
            'status' => 'rejected',
        ])
        ->assertSessionHasErrors('status');
});

it('blocks a non-reviewer from reviewing a request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($requester)
        ->patch(route('lawsuit-requests.review', $lawsuitRequest), [
            'status' => 'approved',
        ])
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Delete
// ────────────────────────────────────────────────────────────────

it('allows the creator to delete a submitted request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::SUBMITTED,
    ]);

    $this->actingAs($requester)
        ->delete(route('lawsuit-requests.destroy', $lawsuitRequest))
        ->assertRedirect(route('lawsuit-requests.index'))
        ->assertSessionHasNoErrors();

    expect(LawsuitFilingRequest::query()->count())->toBe(0);
});

it('blocks deleting an approved request', function (): void {
    $requester = lsrCreateUserWithRole(SystemRole::DEPARTMENT_REQUESTER, hrDepartment());

    $lawsuitRequest = LawsuitFilingRequest::factory()->create([
        'created_by' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'status' => LawsuitRequestStatus::APPROVED,
    ]);

    $this->actingAs($requester)
        ->delete(route('lawsuit-requests.destroy', $lawsuitRequest))
        ->assertForbidden();

    expect(LawsuitFilingRequest::query()->count())->toBe(1);
});

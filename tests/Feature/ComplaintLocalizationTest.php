<?php

declare(strict_types=1);

use App\Enums\ComplaintStatus;
use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
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

it('renders the complaint create page fully in amharic when amharic is active', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);

    $this->actingAs($complainant)
        ->get(route('complaints.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.create_title' => __('complaints.create_title', locale: LocaleCode::AMHARIC->value),
                'complaints.form.labels.complainant_name' => __('complaints.form.labels.complainant_name', locale: LocaleCode::AMHARIC->value),
                'complaints.form.hints.requested_resolution' => __('complaints.form.hints.requested_resolution', locale: LocaleCode::AMHARIC->value),
                'complaints.placeholders.select_department' => __('complaints.placeholders.select_department', locale: LocaleCode::AMHARIC->value),
            ]))
        );
});

it('renders the complaint create page fully in english when english is active', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);

    $this->actingAs($complainant)
        ->get(route('complaints.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('locale', LocaleCode::ENGLISH->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.create_title' => __('complaints.create_title', locale: LocaleCode::ENGLISH->value),
                'complaints.form.labels.complainant_name' => __('complaints.form.labels.complainant_name', locale: LocaleCode::ENGLISH->value),
                'complaints.form.hints.requested_resolution' => __('complaints.form.hints.requested_resolution', locale: LocaleCode::ENGLISH->value),
                'complaints.placeholders.select_department' => __('complaints.placeholders.select_department', locale: LocaleCode::ENGLISH->value),
            ]))
        );
});

it('renders the complaint edit page fully localized in amharic', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);
    $complaint = complaintLocaleFixture($complainant, 'LEG');

    $this->actingAs($complainant)
        ->get(route('complaints.edit', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('mode', 'edit')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.edit_title' => __('complaints.edit_title', locale: LocaleCode::AMHARIC->value),
                'complaints.attachments.existing' => __('complaints.attachments.existing', locale: LocaleCode::AMHARIC->value),
            ]))
        );
});

it('renders the complaint edit page fully localized in english', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);
    $complaint = complaintLocaleFixture($complainant, 'LEG');

    $this->actingAs($complainant)
        ->get(route('complaints.edit', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Create')
            ->where('mode', 'edit')
            ->where('locale', LocaleCode::ENGLISH->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.edit_title' => __('complaints.edit_title', locale: LocaleCode::ENGLISH->value),
                'complaints.attachments.existing' => __('complaints.attachments.existing', locale: LocaleCode::ENGLISH->value),
            ]))
        );
});

it('renders the complaint show page fully localized in amharic', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);
    $complaint = complaintLocaleFixture($complainant, 'LEG', [
        'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
        'is_escalated' => true,
        'forwarded_to_committee_at' => now(),
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Show')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.timeline.title' => __('complaints.timeline.title', locale: LocaleCode::AMHARIC->value),
                'complaints.workflow_snapshot.title' => __('complaints.workflow_snapshot.title', locale: LocaleCode::AMHARIC->value),
                'complaints.escalation_detail.title' => __('complaints.escalation_detail.title', locale: LocaleCode::AMHARIC->value),
            ]))
        );
});

it('renders the complaint show page fully localized in english', function (): void {
    $complainant = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);
    $complaint = complaintLocaleFixture($complainant, 'LEG', [
        'status' => ComplaintStatus::ESCALATED_TO_COMMITTEE,
        'is_escalated' => true,
        'forwarded_to_committee_at' => now(),
    ]);

    $this->actingAs($complainant)
        ->get(route('complaints.show', $complaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Show')
            ->where('locale', LocaleCode::ENGLISH->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.timeline.title' => __('complaints.timeline.title', locale: LocaleCode::ENGLISH->value),
                'complaints.workflow_snapshot.title' => __('complaints.workflow_snapshot.title', locale: LocaleCode::ENGLISH->value),
                'complaints.escalation_detail.title' => __('complaints.escalation_detail.title', locale: LocaleCode::ENGLISH->value),
            ]))
        );
});

it('localizes complaint validation messages according to the active language', function (): void {
    $branch = Branch::query()->firstOrFail();

    $amharicUser = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);

    $this->actingAs($amharicUser)
        ->from(route('complaints.create'))
        ->post(route('complaints.store'), [
            'complainant_name' => 'ጠያቂ',
            'complainant_phone' => '',
            'complainant_city' => 'አዲስ አበባ',
            'branch_id' => $branch->id,
            'department_id' => '',
            'complaint_essence' => '',
            'incident_date' => '',
            'requested_resolution' => '',
        ])
        ->assertRedirect(route('complaints.create'))
        ->assertSessionHasErrors([
            'complainant_phone' => __('validation.required', ['attribute' => __('complaints.validation_attributes.complainant_phone', locale: LocaleCode::AMHARIC->value)], LocaleCode::AMHARIC->value),
            'department_id' => __('validation.required', ['attribute' => __('complaints.validation_attributes.department', locale: LocaleCode::AMHARIC->value)], LocaleCode::AMHARIC->value),
        ]);

    $englishUser = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);

    $this->actingAs($englishUser)
        ->from(route('complaints.create'))
        ->post(route('complaints.store'), [
            'complainant_name' => 'Requester',
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
            'complainant_phone' => __('validation.required', ['attribute' => __('complaints.validation_attributes.complainant_phone', locale: LocaleCode::ENGLISH->value)], LocaleCode::ENGLISH->value),
            'department_id' => __('validation.required', ['attribute' => __('complaints.validation_attributes.department', locale: LocaleCode::ENGLISH->value)], LocaleCode::ENGLISH->value),
        ]);
});

it('localizes complaint success and error messages according to the active language', function (): void {
    $branch = Branch::query()->firstOrFail();
    $department = Department::query()->where('code', 'LEG')->firstOrFail();

    $amharicUser = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);

    $this->actingAs($amharicUser)
        ->post(route('complaints.store'), complaintLocaleSubmissionData($branch->id, $department->id))
        ->assertRedirect()
        ->assertSessionHas('success', __('Complaint submitted successfully.', locale: LocaleCode::AMHARIC->value));

    $englishOwner = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);
    $englishDepartmentUser = complaintLocaleUser(SystemRole::LEGAL_EXPERT, 'LEG', LocaleCode::ENGLISH);
    $complaint = complaintLocaleFixture($englishOwner, 'LEG');

    $this->actingAs($englishDepartmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Department response',
            'response_content' => '<p>First response.</p>',
            'attachments' => [UploadedFile::fake()->create('response.pdf', 12, 'application/pdf')],
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($englishDepartmentUser)
        ->post(route('complaints.respond', $complaint), [
            'subject' => 'Duplicate response',
            'response_content' => '<p>This second response should fail.</p>',
        ])
        ->assertSessionHasErrors([
            'response_content' => __('A department response has already been recorded for this complaint.', locale: LocaleCode::ENGLISH->value),
        ]);
});

it('renders the complaint print view in the active language', function (): void {
    $amharicUser = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::AMHARIC);
    $amharicComplaint = complaintLocaleFixture($amharicUser, 'LEG');

    $this->actingAs($amharicUser)
        ->get(route('complaints.print', $amharicComplaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Print')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.print.title' => __('complaints.print.title', locale: LocaleCode::AMHARIC->value),
                'complaints.print.actions.print' => __('complaints.print.actions.print', locale: LocaleCode::AMHARIC->value),
            ]))
        );

    $englishUser = complaintLocaleUser(SystemRole::DEPARTMENT_REQUESTER, 'HR', LocaleCode::ENGLISH);
    $englishComplaint = complaintLocaleFixture($englishUser, 'LEG');

    $this->actingAs($englishUser)
        ->get(route('complaints.print', $englishComplaint))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Complaints/Print')
            ->where('locale', LocaleCode::ENGLISH->value)
            ->where('translations', complaintTranslationSubset([
                'complaints.print.title' => __('complaints.print.title', locale: LocaleCode::ENGLISH->value),
                'complaints.print.actions.print' => __('complaints.print.actions.print', locale: LocaleCode::ENGLISH->value),
            ]))
        );
});

it('keeps complaint page source free of the known mixed-language complaint literals', function (): void {
    $files = [
        resource_path('js/Pages/Complaints/Create.tsx'),
        resource_path('js/Pages/Complaints/Index.tsx'),
        resource_path('js/Pages/Complaints/Show.tsx'),
        resource_path('js/Pages/Complaints/Print.tsx'),
    ];

    $forbiddenLiterals = [
        'Existing evidence attachments',
        'Complaint Print View',
        'Institutional Complaint Intake',
        'Submitted:',
        'Apply Filters',
        'New Complaint',
        'Complaint management',
    ];

    foreach ($files as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toBeFalse();

        foreach ($forbiddenLiterals as $literal) {
            expect($contents)->not->toContain($literal);
        }
    }
});

function complaintLocaleUser(SystemRole $role, string $departmentCode, LocaleCode $locale): User
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $branch = Branch::query()->firstOrFail();

    $user = User::factory()->create([
        'department_id' => $department->id,
        'branch_id' => $role === SystemRole::COMPLAINT_CLIENT ? null : $branch->id,
        'locale' => $locale,
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+2519'.str_pad((string) fake()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
    ]);

    $user->assignRole($role->value);

    return $user;
}

function complaintLocaleSubmissionData(string $branchId, string $departmentId): array
{
    return [
        'complainant_name' => 'Localized Complainant',
        'complainant_city' => 'Addis Ababa',
        'complainant_sub_city' => 'Bole',
        'complainant_woreda' => '09',
        'complainant_house_number' => 'H-145',
        'complainant_phone' => '+251911223344',
        'complaint_essence' => 'This complaint includes enough localized detail for the complaint form validation and success-message tests.',
        'incident_date' => now()->subDays(2)->toDateString(),
        'branch_id' => $branchId,
        'incident_sub_city' => 'Kirkos',
        'incident_woreda' => '03',
        'department_id' => $departmentId,
        'concerned_employee_name' => 'Concerned service provider',
        'evidence_note' => 'Supporting evidence note.',
        'requested_resolution' => 'Provide a written resolution.',
        'attachments' => [UploadedFile::fake()->create('complaint.pdf', 20, 'application/pdf')],
    ];
}

function complaintLocaleFixture(User $complainant, string $departmentCode, array $overrides = []): Complaint
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $complainantType = $complainant->hasRole(SystemRole::COMPLAINT_CLIENT->value)
        ? 'client'
        : ($complainant->branch_id ? 'branch_employee' : 'head_office_employee');
    $submittedAt = now();

    return Complaint::query()->create(array_merge([
        'complaint_number' => 'CMP-'.fake()->unique()->numerify('2026-####'),
        'complainant_user_id' => $complainant->id,
        'branch_id' => $complainant->branch_id,
        'department_id' => $department->id,
        'complainant_type' => $complainantType,
        'complainant_name' => $complainant->name,
        'complainant_email' => $complainant->email,
        'complainant_phone' => $complainant->phone,
        'complainant_city' => 'Addis Ababa',
        'complainant_sub_city' => 'Bole',
        'complainant_woreda' => '08',
        'complainant_house_number' => 'H-201',
        'subject' => 'Complaint localization test matter',
        'complaint_essence' => 'Structured complaint essence for the localization tests.',
        'details' => '<p>Localized complaint details for the complaint pages.</p>',
        'category' => 'General',
        'evidence_note' => 'Photocopy of service request',
        'requested_resolution' => 'Provide written corrective action.',
        'priority' => 'medium',
        'incident_date' => now()->subDay()->toDateString(),
        'incident_sub_city' => 'Kirkos',
        'incident_woreda' => '05',
        'concerned_employee_name' => 'Case worker name',
        'submitted_at' => $submittedAt,
        'department_response_deadline_at' => now()->addDays(5),
        'status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
    ], $overrides));
}

function complaintTranslationSubset(array $expected): \Closure
{
    return function ($translations) use ($expected): bool {
        $values = collect($translations)->all();

        foreach ($expected as $key => $value) {
            if (($values[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    };
}

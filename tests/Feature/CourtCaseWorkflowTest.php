<?php

declare(strict_types=1);

use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use App\Support\LocalizedDateFormatter;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('moves a court case through registrar director team leader expert and closure', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $director = createCaseUserWithRole(SystemRole::LEGAL_DIRECTOR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $teamLeader = createCaseUserWithRole(SystemRole::LITIGATION_TEAM_LEADER, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());
    $expert = createCaseUserWithRole(SystemRole::LEGAL_EXPERT, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());

    $this->actingAs($registrar)->post(route('cases.store'), [
        'case_number' => 'CASE-2026-5001',
        'main_case_type' => 'civil-law',
        'external_court_file_number' => 'FHC-99887',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', '!=', 'LAB')->firstOrFail()->id,
        'plaintiff' => 'Employee B',
        'defendant' => 'Institution',
        'claim_summary' => '<p>The claimant alleges unlawful termination and unpaid benefits.</p>',
        'status' => 'under_director_review',
        'filing_date' => now()->subWeek()->toDateString(),
        'next_hearing_date' => now()->addWeek()->toDateString(),
        'priority' => 'critical',
    ])->assertRedirect();

    $legalCase = LegalCase::query()->firstOrFail();

    expect($legalCase->status)->toBe(CaseStatus::UNDER_DIRECTOR_REVIEW);

    $this->actingAs($director)->patch(route('cases.review', $legalCase), [
        'director_decision' => 'approved',
        'director_notes' => 'Assign for immediate litigation handling.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_TEAM_LEADER);
    expect($legalCase->workflow_stage)->toBe(WorkflowStage::TEAM_LEADER);

    $this->actingAs($teamLeader)->patch(route('cases.assign', $legalCase), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Prepare witness schedule and payroll exhibits.',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::ASSIGNED_TO_EXPERT);
    expect($legalCase->workflow_stage)->toBe(WorkflowStage::EXPERT);

    $this->actingAs($expert)->post(route('cases.hearings.store', $legalCase), [
        'hearing_date' => now()->toDateString(),
        'next_hearing_date' => now()->addDays(10)->toDateString(),
        'appearance_status' => 'attended',
        'summary' => 'Court heard preliminary objections and requested payroll records.',
        'institution_position' => 'Institution requested time to file supporting documents.',
        'court_decision' => 'Continued to next hearing.',
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::IN_PROGRESS);
    expect($legalCase->hearings)->toHaveCount(1);

    $this->actingAs($teamLeader)->patch(route('cases.close', $legalCase), [
        'outcome' => 'Case closed after negotiated settlement.',
        'closing_date' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $legalCase->refresh();

    expect($legalCase->status)->toBe(CaseStatus::CLOSED);
});

it('shows the updated assignment status after each court case assignment step', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $director = createCaseUserWithRole(SystemRole::LEGAL_DIRECTOR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $teamLeader = createCaseUserWithRole(SystemRole::LITIGATION_TEAM_LEADER, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());
    $expert = createCaseUserWithRole(SystemRole::LEGAL_EXPERT, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-2026-STATUS-1',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Status Plaintiff',
        'defendant' => 'Status Defendant',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Status should change automatically during assignment.</p>',
    ]);

    $this->actingAs($director)->patch(route('cases.review', $legalCase), [
        'director_decision' => 'approved',
        'director_notes' => 'Move to litigation lead.',
        'assigned_team_leader_id' => $teamLeader->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($director)
        ->get(route('cases.show', ['legalCase' => $legalCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.status', CaseStatus::ASSIGNED_TO_TEAM_LEADER->value)
            ->where('caseItem.workflow_stage', WorkflowStage::TEAM_LEADER->value)
        );

    $this->actingAs($teamLeader)->patch(route('cases.assign', $legalCase), [
        'assigned_legal_expert_id' => $expert->id,
        'notes' => 'Move to expert handling.',
    ])->assertSessionHasNoErrors();

    $this->actingAs($teamLeader)
        ->get(route('cases.show', ['legalCase' => $legalCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.status', CaseStatus::ASSIGNED_TO_EXPERT->value)
            ->where('caseItem.workflow_stage', WorkflowStage::EXPERT->value)
        );
});

it('creates and saves a civil-law case with the dynamic form payload', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $this->actingAs($registrar)->post(route('cases.store'), [
        'case_number' => 'CASE-CIV-0001',
        'main_case_type' => 'civil-law',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', '!=', 'LAB')->firstOrFail()->id,
        'plaintiff' => 'Alpha Trading',
        'defendant' => 'Institution',
        'status' => 'under_director_review',
        'claim_summary' => '<p>Detailed civil-law dispute description.</p>',
        'amount' => '250000.50',
        'priority' => 'medium',
    ])->assertRedirect();

    $legalCase = LegalCase::query()->where('case_number', 'CASE-CIV-0001')->firstOrFail();

    expect($legalCase->main_case_type->value)->toBe('civil-law');
    expect($legalCase->plaintiff)->toBe('Alpha Trading');
    expect((string) $legalCase->amount)->toBe('250000.50');
    expect($legalCase->claim_summary)->toBe('<p>Detailed civil-law dispute description.</p>');
});

it('creates and saves a crime case with the dynamic form payload', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $this->actingAs($registrar)->post(route('cases.store'), [
        'case_number' => 'CASE-CRM-0001',
        'main_case_type' => 'crime',
        'crime_scene' => 'Arat Kilo',
        'police_station' => 'Addis Ababa Police Station',
        'stolen_property_type' => 'Laptop computers',
        'stolen_property_estimated_value' => '120000',
        'suspect_names' => 'Suspect One, Suspect Two',
        'statement_date' => now()->subDay()->toDateString(),
        'status' => 'intake',
        'claim_summary' => '<p>Particulars of the offense for the crime case.</p>',
        'priority' => 'high',
    ])->assertRedirect();

    $legalCase = LegalCase::query()->where('case_number', 'CASE-CRM-0001')->firstOrFail();

    expect($legalCase->main_case_type->value)->toBe('crime');
    expect($legalCase->court_id)->toBeNull();
    expect($legalCase->crime_scene)->toBe('Arat Kilo');
    expect($legalCase->suspect_names)->toBe('Suspect One, Suspect Two');
});

it('creates and saves a labour-dispute case with the dynamic form payload', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $this->actingAs($registrar)->post(route('cases.store'), [
        'case_number' => 'CASE-LAB-0001',
        'main_case_type' => 'labour-dispute',
        'court_id' => Court::query()->firstOrFail()->id,
        'plaintiff' => 'Former Employee',
        'defendant' => 'Institution',
        'status' => 'under_director_review',
        'claim_summary' => '<p>Detailed labour dispute description.</p>',
        'amount' => '50000',
        'priority' => 'medium',
    ])->assertRedirect();

    $legalCase = LegalCase::query()->where('case_number', 'CASE-LAB-0001')->firstOrFail();

    expect($legalCase->main_case_type->value)->toBe('labour-dispute');
    expect($legalCase->caseType?->code)->toBe('LAB');
    expect($legalCase->claim_summary)->toBe('<p>Detailed labour dispute description.</p>');
});

it('loads the edit page with the correct conditional case fields', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-EDIT-0001',
        'main_case_type' => 'crime',
        'registered_by_id' => $registrar->id,
        'crime_scene' => 'Piassa',
        'police_station' => 'Central Station',
        'stolen_property_type' => 'Mobile phones',
        'suspect_names' => 'Suspect A',
        'statement_date' => now()->toDateString(),
        'status' => CaseStatus::INTAKE,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Saved crime details.</p>',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.edit', ['legalCase' => $legalCase]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Create')
            ->where('mode', 'edit')
            ->where('caseItem.id', $legalCase->id)
            ->where('caseItem.main_case_type', 'crime')
            ->where('caseItem.crime_scene', 'Piassa')
        );
});

it('shows only civil-law relevant overview fields on the case show page', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SHOW-CIV-1',
        'external_court_file_number' => 'FHC-CIV-7788',
        'main_case_type' => 'civil-law',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', '!=', 'LAB')->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Civil Plaintiff',
        'defendant' => 'Civil Defendant',
        'amount' => '75000',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Civil overview description.</p>',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.overview_fields', overviewFieldsMatch([
                'case_number',
                'main_case_type',
                'status',
                'registrar',
                'court_file_number',
                'team_leader',
                'expert',
                'court',
                'civil_law_type',
                'plaintiff',
                'defendant',
                'amount',
            ], [
                'crime_scene',
                'police_station',
                'stolen_property_type',
                'stolen_property_estimated_value',
                'suspect_names',
                'statement_date',
            ]))
            ->where('caseItem.overview_description_label', __('cases.detailed_description'))
            ->where('caseItem.overview_description_html', '<p>Civil overview description.</p>')
        );
});

it('shows only crime relevant overview fields on the case show page', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SHOW-CRM-1',
        'external_court_file_number' => 'CRM-42',
        'main_case_type' => 'crime',
        'registered_by_id' => $registrar->id,
        'crime_scene' => 'Piassa',
        'police_station' => 'Central Station',
        'stolen_property_type' => 'Mobile Phones',
        'stolen_property_estimated_value' => '125000',
        'suspect_names' => 'Suspect A, Suspect B',
        'statement_date' => now()->subDay()->toDateString(),
        'status' => CaseStatus::INTAKE,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Crime overview description.</p>',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.overview_fields', overviewFieldsMatch([
                'case_number',
                'main_case_type',
                'status',
                'registrar',
                'court_file_number',
                'team_leader',
                'expert',
                'crime_scene',
                'police_station',
                'stolen_property_type',
                'stolen_property_estimated_value',
                'suspect_names',
                'statement_date',
            ], [
                'plaintiff',
                'defendant',
                'amount',
                'civil_law_type',
            ]))
            ->where('caseItem.overview_description_label', __('cases.crime_details'))
            ->where('caseItem.overview_description_html', '<p>Crime overview description.</p>')
        );
});

it('shows only labour-dispute relevant overview fields and hides empty optional values', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SHOW-LAB-1',
        'main_case_type' => 'labour-dispute',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', 'LAB')->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Former Employee',
        'defendant' => 'Institution',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'amount' => null,
        'claim_summary' => '',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.overview_fields', overviewFieldsMatch([
                'case_number',
                'main_case_type',
                'status',
                'registrar',
                'team_leader',
                'expert',
                'court',
                'plaintiff',
                'defendant',
            ], [
                'amount',
                'civil_law_type',
                'crime_scene',
                'police_station',
                'stolen_property_type',
                'stolen_property_estimated_value',
                'suspect_names',
                'statement_date',
            ]))
            ->where('caseItem.overview_description_html', null)
        );
});

it('renders old incomplete case records safely without non-applicable not-set overview rows', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SHOW-OLD-1',
        'main_case_type' => 'crime',
        'registered_by_id' => $registrar->id,
        'status' => CaseStatus::INTAKE,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::LOW,
        'director_decision' => 'pending',
        'claim_summary' => '',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('caseItem.overview_fields', overviewFieldsWithoutFallbackPlaceholders())
            ->where('caseItem.overview_description_html', null)
        );
});

it('formats overview dates in ethiopic form when the active locale is amharic', function (): void {
    $registrar = createCaseUserWithRole(
        SystemRole::REGISTRAR,
        Department::query()->where('code', 'LEG')->firstOrFail(),
        Team::query()->where('code', 'ADM')->firstOrFail(),
    );
    $registrar->forceFill(['locale' => LocaleCode::AMHARIC->value])->save();

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SHOW-AM-DATE-1',
        'main_case_type' => 'crime',
        'registered_by_id' => $registrar->id,
        'crime_scene' => 'Merkato',
        'police_station' => 'Addis Police',
        'stolen_property_type' => 'Documents',
        'suspect_names' => 'Suspect One',
        'statement_date' => '2026-05-08',
        'next_hearing_date' => '2026-05-09',
        'status' => CaseStatus::INTAKE,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Crime details.</p>',
    ]);

    $expectedStatementDate = app(LocalizedDateFormatter::class)->formatDate('2026-05-08', 'am', 'Africa/Addis_Ababa');
    $expectedNextHearingDate = app(LocalizedDateFormatter::class)->formatDate('2026-05-09', 'am', 'Africa/Addis_Ababa');

    $this->actingAs($registrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('caseItem.overview_fields', function ($fields) use ($expectedStatementDate, $expectedNextHearingDate): bool {
                if ($fields instanceof \Illuminate\Support\Collection) {
                    $fields = $fields->all();
                } elseif ($fields instanceof \Traversable) {
                    $fields = iterator_to_array($fields);
                }

                if (! is_array($fields)) {
                    return false;
                }

                $indexed = collect($fields)
                    ->filter(fn ($field) => is_array($field) && isset($field['key']))
                    ->mapWithKeys(fn ($field) => [$field['key'] => $field['value'] ?? null]);

                return $indexed->get('statement_date') === $expectedStatementDate
                    && $indexed->get('next_hearing') === $expectedNextHearingDate;
            })
        );
});

it('rejects an incomplete crime submission and keeps the entered case number in old input', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $this->actingAs($registrar)
        ->from(route('cases.create'))
        ->post(route('cases.store'), [
            'case_number' => 'CASE-CRM-ERROR',
            'main_case_type' => 'crime',
            'police_station' => 'Central Station',
            'status' => 'intake',
            'priority' => 'medium',
        ])
        ->assertRedirect(route('cases.create'))
        ->assertSessionHasErrors(['crime_scene', 'stolen_property_type', 'suspect_names', 'statement_date', 'claim_summary'])
        ->assertSessionHasInput('case_number', 'CASE-CRM-ERROR');
});

it('filters the case index by main case type and exposes the correct row data', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());
    $teamLeader = createCaseUserWithRole(SystemRole::LITIGATION_TEAM_LEADER, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());
    $expert = createCaseUserWithRole(SystemRole::LEGAL_EXPERT, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'LIT')->firstOrFail());

    $civilCase = LegalCase::query()->create([
        'case_number' => 'CASE-CIV-FILTER',
        'external_court_file_number' => 'FHC-CIV-1001',
        'main_case_type' => 'civil-law',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', '!=', 'LAB')->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Civil Plaintiff',
        'defendant' => 'Civil Defendant',
        'status' => CaseStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => 'approved',
        'claim_summary' => '<p>Civil filter payload.</p>',
    ]);

    $labourCase = LegalCase::query()->create([
        'case_number' => 'CASE-LAB-FILTER',
        'external_court_file_number' => 'LAB-2002',
        'main_case_type' => 'labour-dispute',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->where('code', 'LAB')->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Labour Plaintiff',
        'defendant' => 'Labour Defendant',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Labour filter payload.</p>',
    ]);

    $crimeCase = LegalCase::query()->create([
        'case_number' => 'CASE-CRM-FILTER',
        'main_case_type' => 'crime',
        'registered_by_id' => $registrar->id,
        'crime_scene' => 'Arat Kilo',
        'police_station' => 'Central Police Station',
        'stolen_property_type' => 'Laptops',
        'suspect_names' => 'Suspect One',
        'statement_date' => now()->toDateString(),
        'status' => CaseStatus::INTAKE,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::LOW,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Crime filter payload.</p>',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.index', ['main_case_type' => 'civil-law']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->where('filters.main_case_type', 'civil-law')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $civilCase->id)
            ->where('cases.data.0.case_number', 'CASE-CIV-FILTER')
            ->where('cases.data.0.external_court_file_number', 'FHC-CIV-1001')
            ->where('cases.data.0.assigned_team_leader.name', $teamLeader->name)
            ->where('cases.data.0.assigned_legal_expert.name', $expert->name)
            ->where('cases.data.0.plaintiff', 'Civil Plaintiff')
            ->where('cases.data.0.defendant', 'Civil Defendant')
        );

    $this->actingAs($registrar)
        ->get(route('cases.index', ['main_case_type' => 'labour-dispute']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->where('filters.main_case_type', 'labour-dispute')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $labourCase->id)
            ->where('cases.data.0.case_number', 'CASE-LAB-FILTER')
            ->where('cases.data.0.plaintiff', 'Labour Plaintiff')
            ->where('cases.data.0.defendant', 'Labour Defendant')
        );

    $this->actingAs($registrar)
        ->get(route('cases.index', ['main_case_type' => 'crime']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->where('filters.main_case_type', 'crime')
            ->has('cases.data', 1)
            ->where('cases.data.0.id', $crimeCase->id)
            ->where('cases.data.0.case_number', 'CASE-CRM-FILTER')
            ->where('cases.data.0.police_station', 'Central Police Station')
            ->where('cases.data.0.stolen_property_type', 'Laptops')
        );
});

it('allows an eligible registrar to delete a selected case from the index flow', function (): void {
    $registrar = createCaseUserWithRole(SystemRole::REGISTRAR, Department::query()->where('code', 'LEG')->firstOrFail(), Team::query()->where('code', 'ADM')->firstOrFail());

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-DELETE-0001',
        'main_case_type' => 'labour-dispute',
        'court_id' => Court::query()->firstOrFail()->id,
        'registered_by_id' => $registrar->id,
        'plaintiff' => 'Delete Plaintiff',
        'defendant' => 'Delete Defendant',
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => 'pending',
        'claim_summary' => '<p>Delete me safely.</p>',
    ]);

    $this->actingAs($registrar)
        ->get(route('cases.index', ['main_case_type' => 'labour-dispute']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Index')
            ->where('cases.data.0.id', $legalCase->id)
            ->where('cases.data.0.can_update', true)
            ->where('cases.data.0.can_delete', true)
        );

    $this->actingAs($registrar)
        ->delete(route('cases.destroy', ['legalCase' => $legalCase]))
        ->assertRedirect();

    $this->assertSoftDeleted('legal_cases', [
        'id' => $legalCase->id,
    ]);
});

function createCaseUserWithRole(SystemRole $role, Department $department, ?Team $team = null): User
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

function overviewFieldsMatch(array $expectedKeys, array $forbiddenKeys): \Closure
{
    return function ($fields) use ($expectedKeys, $forbiddenKeys): bool {
        if ($fields instanceof \Illuminate\Support\Collection) {
            $fields = $fields->all();
        } elseif ($fields instanceof \Traversable) {
            $fields = iterator_to_array($fields);
        }

        if (! is_array($fields)) {
            return false;
        }

        $keys = collect($fields)
            ->map(fn ($field) => is_array($field) ? ($field['key'] ?? null) : null)
            ->filter()
            ->all();

        foreach ($expectedKeys as $key) {
            if (! in_array($key, $keys, true)) {
                return false;
            }
        }

        foreach ($forbiddenKeys as $key) {
            if (in_array($key, $keys, true)) {
                return false;
            }
        }

        foreach ($fields as $field) {
            if (is_array($field) && (($field['value'] ?? null) === __('common.not_set'))) {
                return false;
            }
        }

        return true;
    };
}

function overviewFieldsWithoutFallbackPlaceholders(): \Closure
{
    return function ($fields): bool {
        if ($fields instanceof \Illuminate\Support\Collection) {
            $fields = $fields->all();
        } elseif ($fields instanceof \Traversable) {
            $fields = iterator_to_array($fields);
        }

        if (! is_array($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (is_array($field) && (($field['value'] ?? null) === __('common.not_set'))) {
                return false;
            }
        }

        return true;
    };
}

<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\TeamType;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('allows a super admin to manage all master data modules', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)->post(route('departments.store'), [
        'code' => 'ICTX',
        'name_en' => 'ICT',
        'name_am' => 'አይሲቲ',
        'is_active' => true,
    ])->assertRedirect();

    $department = Department::query()->where('code', 'ICTX')->firstOrFail();

    $this->actingAs($admin)->post(route('teams.store'), [
        'code' => 'ADM-LIT-X',
        'name_en' => 'Admin Litigation Team',
        'name_am' => 'የአስተዳደር የክስ ቡድን',
        'type' => TeamType::LITIGATION->value,
        'leader_user_id' => $admin->id,
        'is_active' => true,
    ])->assertRedirect();

    $team = Team::query()->where('code', 'ADM-LIT-X')->firstOrFail();

    $this->actingAs($admin)->post(route('advisory-categories.store'), [
        'code' => 'PROC-X',
        'name_en' => 'Procurement',
        'name_am' => 'ግዥ',
        'description' => 'Procurement legal advice',
        'is_active' => true,
    ])->assertRedirect();

    $category = AdvisoryCategory::query()->where('code', 'PROC-X')->firstOrFail();

    $this->actingAs($admin)->post(route('courts.store'), [
        'code' => 'FED-SC-X',
        'name_en' => 'Federal Supreme Court',
        'name_am' => 'የፌዴራል ጠቅላይ ፍርድ ቤት',
        'level' => 'Federal',
        'city' => 'Addis Ababa',
        'is_active' => true,
    ])->assertRedirect();

    $court = Court::query()->where('code', 'FED-SC-X')->firstOrFail();

    $this->actingAs($admin)->post(route('legal-case-types.store'), [
        'code' => 'LABOUR-X',
        'name_en' => 'Labour',
        'name_am' => 'ሰራተኛ',
        'description' => 'Labour dispute cases',
        'is_active' => true,
    ])->assertRedirect();

    $caseType = CaseType::query()->where('code', 'LABOUR-X')->firstOrFail();

    $this->actingAs($admin)->patch(route('departments.update', $department), [
        'code' => 'ICTX',
        'name_en' => 'Information Technology',
        'name_am' => 'ኢንፎርሜሽን ቴክኖሎጂ',
        'is_active' => false,
    ])->assertRedirect(route('departments.edit', $department));

    $this->actingAs($admin)->patch(route('teams.update', $team), [
        'code' => 'ADM-LIT-X',
        'name_en' => 'Admin Litigation Team Updated',
        'name_am' => 'የአስተዳደር የክስ ቡድን የተዘምነ',
        'type' => TeamType::ADVISORY->value,
        'leader_user_id' => $admin->id,
        'is_active' => false,
    ])->assertRedirect(route('teams.edit', $team));

    $this->actingAs($admin)->patch(route('advisory-categories.update', $category), [
        'code' => 'PROC-X',
        'name_en' => 'Procurement and Contracts',
        'name_am' => 'ግዥ እና ውል',
        'description' => 'Updated description',
        'is_active' => false,
    ])->assertRedirect(route('advisory-categories.edit', $category));

    $this->actingAs($admin)->patch(route('courts.update', $court), [
        'code' => 'FED-SC-X',
        'name_en' => 'Federal Supreme Court Updated',
        'name_am' => 'የፌዴራል ጠቅላይ ፍርድ ቤት የተዘምነ',
        'level' => 'Federal',
        'city' => 'Adama',
        'is_active' => false,
    ])->assertRedirect(route('courts.edit', $court));

    $this->actingAs($admin)->patch(route('legal-case-types.update', $caseType), [
        'code' => 'LABOUR-X',
        'name_en' => 'Labour and Employment',
        'name_am' => 'ሰራተኛ እና ቅጥር',
        'description' => 'Updated description',
        'is_active' => false,
    ])->assertRedirect(route('legal-case-types.edit', $caseType));

    expect($department->fresh()?->is_active)->toBeFalse()
        ->and($team->fresh()?->type)->toBe(TeamType::ADVISORY)
        ->and($category->fresh()?->is_active)->toBeFalse()
        ->and($court->fresh()?->city)->toBe('Adama')
        ->and($caseType->fresh()?->name_en)->toBe('Labour and Employment');

    $this->actingAs($admin)->delete(route('departments.destroy', $department))->assertRedirect(route('departments.index'));
    $this->actingAs($admin)->delete(route('teams.destroy', $team))->assertRedirect(route('teams.index'));
    $this->actingAs($admin)->delete(route('advisory-categories.destroy', $category))->assertRedirect(route('advisory-categories.index'));
    $this->actingAs($admin)->delete(route('courts.destroy', $court))->assertRedirect(route('courts.index'));
    $this->actingAs($admin)->delete(route('legal-case-types.destroy', $caseType))->assertRedirect(route('legal-case-types.index'));

    $this->assertSoftDeleted('departments', ['id' => $department->id]);
    $this->assertSoftDeleted('teams', ['id' => $team->id]);
    $this->assertSoftDeleted('advisory_categories', ['id' => $category->id]);
    $this->assertSoftDeleted('courts', ['id' => $court->id]);
    $this->assertSoftDeleted('case_types', ['id' => $caseType->id]);
});

it('allows legal directors to view master data but not manage it', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $department = Department::query()->firstOrFail();
    $team = Team::query()->firstOrFail();
    $category = AdvisoryCategory::query()->firstOrFail();
    $court = Court::query()->firstOrFail();
    $caseType = CaseType::query()->firstOrFail();

    $this->actingAs($director)
        ->get(route('departments.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Departments/Index')
            ->where('can.create', false)
            ->where('can.update', false));

    $this->actingAs($director)->get(route('teams.index'))->assertOk();
    $this->actingAs($director)->get(route('advisory-categories.index'))->assertOk();
    $this->actingAs($director)->get(route('courts.index'))->assertOk();
    $this->actingAs($director)->get(route('legal-case-types.index'))->assertOk();
    $this->actingAs($director)->get(route('departments.show', $department))->assertOk();
    $this->actingAs($director)->get(route('teams.show', $team))->assertOk();
    $this->actingAs($director)->get(route('advisory-categories.show', $category))->assertOk();
    $this->actingAs($director)->get(route('courts.show', $court))->assertOk();
    $this->actingAs($director)->get(route('legal-case-types.show', $caseType))->assertOk();

    $this->actingAs($director)->get(route('departments.create'))->assertForbidden();
    $this->actingAs($director)->post(route('departments.store'), [
        'code' => 'FIN',
        'name_en' => 'Finance',
        'name_am' => 'ፋይናንስ',
        'is_active' => true,
    ])->assertForbidden();
});

it('prevents deleting master data records that are still linked to active records', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();
    $expert = User::query()->where('email', 'expert.one@ldms.test')->firstOrFail();
    $registrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();
    $litigationLead = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $litigationExpert = User::query()->where('email', 'expert.two@ldms.test')->firstOrFail();

    $department = Department::query()->where('code', 'LEG')->firstOrFail();
    $team = Team::query()->where('code', 'ADV')->firstOrFail();
    $category = AdvisoryCategory::query()->firstOrFail();
    $court = Court::query()->firstOrFail();
    $caseType = CaseType::query()->firstOrFail();

    AdvisoryRequest::query()->create([
        'request_number' => 'ADV-MASTER-LOCK',
        'department_id' => $department->id,
        'category_id' => $category->id,
        'requester_user_id' => $requester->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Reference data guard',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Used to verify delete protections.',
        'date_submitted' => now()->toDateString(),
    ]);

    LegalCase::query()->create([
        'case_number' => 'CASE-MASTER-LOCK',
        'court_id' => $court->id,
        'case_type_id' => $caseType->id,
        'registered_by_id' => $registrar->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $litigationLead->id,
        'assigned_legal_expert_id' => $litigationExpert->id,
        'plaintiff' => 'Linked Plaintiff',
        'defendant' => 'Institution',
        'bench_or_chamber' => 'Bench A',
        'status' => CaseStatus::IN_PROGRESS,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'claim_summary' => 'Protect linked court and case type records.',
        'institution_position' => 'Defend and retain references.',
        'filing_date' => now()->subDays(2)->toDateString(),
    ]);

    $this->actingAs($admin)
        ->delete(route('departments.destroy', $department))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('teams.destroy', $team))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('advisory-categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('courts.destroy', $court))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->delete(route('legal-case-types.destroy', $caseType))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('departments', ['id' => $department->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('teams', ['id' => $team->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('advisory_categories', ['id' => $category->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('courts', ['id' => $court->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('case_types', ['id' => $caseType->id, 'deleted_at' => null]);
});

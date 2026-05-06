<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\TeamType;
use App\Models\AdvisoryRequest;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('allows team creation for advisory only support', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)->post(route('teams.store'), [
        'code' => 'ADV-ONLY-X',
        'name_en' => 'Advisory Only Team',
        'name_am' => 'የህግ ምክር ብቻ ቡድን',
        'supports_advisory' => true,
        'supports_court_case' => false,
        'leader_user_id' => $admin->id,
        'is_active' => true,
    ])->assertRedirect();

    $team = Team::query()->where('code', 'ADV-ONLY-X')->firstOrFail();

    expect($team->supports_advisory)->toBeTrue()
        ->and($team->supports_court_case)->toBeFalse()
        ->and($team->type)->toBe(TeamType::ADVISORY);
});

it('allows team creation for court case only support', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)->post(route('teams.store'), [
        'code' => 'CASE-ONLY-X',
        'name_en' => 'Court Case Only Team',
        'name_am' => 'የክስ ጉዳይ ብቻ ቡድን',
        'supports_advisory' => false,
        'supports_court_case' => true,
        'leader_user_id' => $admin->id,
        'is_active' => true,
    ])->assertRedirect();

    $team = Team::query()->where('code', 'CASE-ONLY-X')->firstOrFail();

    expect($team->supports_advisory)->toBeFalse()
        ->and($team->supports_court_case)->toBeTrue()
        ->and($team->type)->toBe(TeamType::LITIGATION);
});

it('allows team creation for both advisory and court case support', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)->post(route('teams.store'), [
        'code' => 'BOTH-X',
        'name_en' => 'Shared Legal Team',
        'name_am' => 'የጋራ የህግ ቡድን',
        'supports_advisory' => true,
        'supports_court_case' => true,
        'leader_user_id' => $admin->id,
        'is_active' => true,
    ])->assertRedirect();

    $team = Team::query()->where('code', 'BOTH-X')->firstOrFail();

    expect($team->supports_advisory)->toBeTrue()
        ->and($team->supports_court_case)->toBeTrue();
});

it('rejects team creation when no workflow support is selected', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('teams.create'))
        ->post(route('teams.store'), [
            'code' => 'NONE-X',
            'name_en' => 'Unusable Team',
            'name_am' => 'የማይጠቀም ቡድን',
            'supports_advisory' => false,
            'supports_court_case' => false,
            'leader_user_id' => $admin->id,
            'is_active' => true,
        ])
        ->assertRedirect(route('teams.create'))
        ->assertSessionHasErrors('supports_advisory');
});

it('preloads saved team capabilities on edit', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $team = Team::query()->create([
        'code' => 'EDIT-BOTH-X',
        'name_en' => 'Editable Shared Team',
        'name_am' => 'ለማስተካከል የሚቻል የጋራ ቡድን',
        'leader_user_id' => $admin->id,
        'type' => TeamType::ADMINISTRATION,
        'supports_advisory' => true,
        'supports_court_case' => true,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('teams.edit', $team))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Teams/Form')
            ->where('teamItem.supports_advisory', true)
            ->where('teamItem.supports_court_case', true));
});

it('shows advisory workflow selectors for users on advisory-supporting teams', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();
    $expert = User::query()->where('email', 'expert.one@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();

    $advisoryRequest->update([
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => \App\Enums\WorkflowStage::DIRECTOR,
        'assigned_team_leader_id' => null,
        'assigned_legal_expert_id' => null,
    ]);

    Team::query()->where('code', 'ADV')->update([
        'supports_advisory' => false,
        'supports_court_case' => false,
    ]);

    $sharedTeam = Team::query()->create([
        'code' => 'ADV-SHARED-X',
        'name_en' => 'Advisory Shared Team',
        'name_am' => 'የህግ ምክር የጋራ ቡድን',
        'leader_user_id' => $teamLeader->id,
        'type' => TeamType::ADVISORY,
        'supports_advisory' => true,
        'supports_court_case' => false,
        'is_active' => true,
    ]);

    $teamLeader->update(['team_id' => $sharedTeam->id]);
    $expert->update(['team_id' => $sharedTeam->id]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $teamLeader->id)
            ->has('experts', 1)
            ->where('experts.0.id', $expert->id));
});

it('shows court case workflow selectors for users on court-case-supporting teams', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $expert = User::query()->where('email', 'expert.two@ldms.test')->firstOrFail();
    $legalCase = LegalCase::query()->where('case_number', 'CASE-2026-0001')->firstOrFail();

    $legalCase->update([
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => \App\Enums\WorkflowStage::DIRECTOR,
        'assigned_team_leader_id' => null,
        'assigned_legal_expert_id' => null,
    ]);

    Team::query()->where('code', 'LIT')->update([
        'supports_advisory' => false,
        'supports_court_case' => false,
    ]);

    $sharedTeam = Team::query()->create([
        'code' => 'CASE-SHARED-X',
        'name_en' => 'Court Case Shared Team',
        'name_am' => 'የክስ ጉዳይ የጋራ ቡድን',
        'leader_user_id' => $teamLeader->id,
        'type' => TeamType::LITIGATION,
        'supports_advisory' => false,
        'supports_court_case' => true,
        'is_active' => true,
    ]);

    $teamLeader->update(['team_id' => $sharedTeam->id]);
    $expert->update(['team_id' => $sharedTeam->id]);

    $this->actingAs($director)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $teamLeader->id)
            ->has('experts', 1)
            ->where('experts.0.id', $expert->id));
});

it('allows one shared team to appear in both advisory and court case workflows', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $advisoryLead = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();
    $advisoryExpert = User::query()->where('email', 'expert.one@ldms.test')->firstOrFail();
    $litigationLead = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $litigationExpert = User::query()->where('email', 'expert.two@ldms.test')->firstOrFail();
    $advisoryRequest = AdvisoryRequest::query()->where('request_number', 'ADV-2026-0001')->firstOrFail();
    $legalCase = LegalCase::query()->where('case_number', 'CASE-2026-0001')->firstOrFail();

    $advisoryRequest->update([
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => \App\Enums\WorkflowStage::DIRECTOR,
        'assigned_team_leader_id' => null,
        'assigned_legal_expert_id' => null,
    ]);

    $legalCase->update([
        'status' => CaseStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => \App\Enums\WorkflowStage::DIRECTOR,
        'assigned_team_leader_id' => null,
        'assigned_legal_expert_id' => null,
    ]);

    Team::query()->whereIn('code', ['ADV', 'LIT'])->update([
        'supports_advisory' => false,
        'supports_court_case' => false,
    ]);

    $sharedTeam = Team::query()->create([
        'code' => 'BOTH-SHARED-X',
        'name_en' => 'Dual Workflow Team',
        'name_am' => 'ሁለቱንም የሚደግፍ ቡድን',
        'leader_user_id' => $advisoryLead->id,
        'type' => TeamType::ADMINISTRATION,
        'supports_advisory' => true,
        'supports_court_case' => true,
        'is_active' => true,
    ]);

    User::query()->whereIn('id', [
        $advisoryLead->id,
        $advisoryExpert->id,
        $litigationLead->id,
        $litigationExpert->id,
    ])->update(['team_id' => $sharedTeam->id]);

    $this->actingAs($director)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $advisoryLead->id)
            ->has('experts', 1)
            ->where('experts.0.id', $advisoryExpert->id));

    $this->actingAs($director)
        ->get(route('cases.show', $legalCase))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Cases/Show')
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $litigationLead->id)
            ->has('experts', 1)
            ->where('experts.0.id', $litigationExpert->id));
});

it('keeps legacy non-workflow teams loadable after capability migration', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $legacyTeam = Team::query()->where('code', 'ADM')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('teams.edit', $legacyTeam))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Teams/Form')
            ->where('teamItem.supports_advisory', false)
            ->where('teamItem.supports_court_case', false));
});

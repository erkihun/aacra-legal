<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('prevents renaming the super admin role', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::SUPER_ADMIN->value)->firstOrFail();

    $this->actingAs($admin)
        ->from(route('roles.edit', $role))
        ->patch(route('roles.update', $role), [
            'name' => 'Platform Root',
            'permissions' => $role->permissions()->pluck('name')->all(),
        ])
        ->assertSessionHas('error', __('roles.system_name_locked_error'));

    expect($role->fresh()?->name)->toBe(SystemRole::SUPER_ADMIN->value);
});

it('prevents deleting the super admin role', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::SUPER_ADMIN->value)->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('roles.destroy', $role))
        ->assertSessionHas('error', __('roles.system_delete_error'));

    $this->assertDatabaseHas('roles', [
        'id' => $role->getKey(),
        'name' => SystemRole::SUPER_ADMIN->value,
    ]);
});

it('allows renaming non-super-admin roles', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail();
    $permissions = $role->permissions()->pluck('name')->all();

    $this->actingAs($admin)
        ->patch(route('roles.update', $role), [
            'name' => 'Operations Review Lead',
            'permissions' => $permissions,
        ])
        ->assertRedirect(route('roles.edit', $role));

    expect($role->fresh()?->name)->toBe('Operations Review Lead')
        ->and($role->fresh()?->permissions()->pluck('name')->all())
        ->toEqualCanonicalizing($permissions);
});

it('keeps advisory workflow access working after renaming a non-super-admin role through role management', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $teamLeader = User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail();
    $role = Role::query()->where('name', SystemRole::LEGAL_DIRECTOR->value)->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('roles.update', $role), [
            'name' => 'Operations Review Lead',
            'permissions' => $role->permissions()->pluck('name')->all(),
        ])
        ->assertRedirect(route('roles.edit', $role));

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ROLE-MGMT-0001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'subject' => 'Role management rename workflow check',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::PENDING,
        'description' => 'Workflow access should continue to depend on permissions after a role rename.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($director->fresh())
        ->get(route('advisory.show', $advisoryRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can.review', true)
            ->where('workspace.canAssignTeamLeader', true)
            ->has('teamLeaders', 1)
            ->where('teamLeaders.0.id', $teamLeader->id));

    $this->actingAs($director->fresh())
        ->patch(route('advisory.review', $advisoryRequest), [
            'director_decision' => 'approved',
            'director_notes' => 'Approved after role-management rename.',
            'assigned_team_leader_id' => $teamLeader->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($advisoryRequest->fresh()?->assigned_team_leader_id)->toBe($teamLeader->id);
});

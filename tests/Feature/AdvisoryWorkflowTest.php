<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;

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
        'response_type' => 'written',
        'summary' => 'The department may proceed once notice and evaluation records are preserved.',
        'advice_text' => 'Keep the bid-evaluation memo, bidder communication record, and appeal timeline on file.',
        'follow_up_notes' => 'Brief procurement team this week.',
    ])->assertSessionHasNoErrors();

    $advisoryRequest->refresh();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::RESPONDED);
    expect($advisoryRequest->responses)->toHaveCount(1);
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

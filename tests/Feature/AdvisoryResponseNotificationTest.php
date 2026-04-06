<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AdvisoryResponseRecordedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('notifies the advisory requester when a response is created and does not notify unrelated users', function (): void {
    Notification::fake();

    $requester = createAdvisoryUserWithRole(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $teamLeader = createAdvisoryUserWithRole(SystemRole::ADVISORY_TEAM_LEADER, 'LEG', 'ADV');
    $expert = createAdvisoryUserWithRole(SystemRole::LEGAL_EXPERT, 'LEG', 'ADV');
    $unrelatedUser = createAdvisoryUserWithRole(SystemRole::DEPARTMENT_REQUESTER, 'FIN');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9301',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Requester notification on advisory response',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Requester should be notified when the response is recorded.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($expert)
        ->post(route('advisory.respond', $advisoryRequest), [
            'subject' => 'Operational guidance provided',
            'response' => '<p>The requested advisory response is ready for review.</p>',
        ])
        ->assertRedirect(route('advisory.show', $advisoryRequest))
        ->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $requester,
        AdvisoryResponseRecordedNotification::class,
        function (AdvisoryResponseRecordedNotification $notification) use ($advisoryRequest, $requester): bool {
            $payload = $notification->toArray($requester);

            return $payload['type'] === 'advisory.response_recorded'
                && $payload['request_number'] === $advisoryRequest->request_number
                && $payload['subject'] === $advisoryRequest->subject
                && $payload['responder_name'] !== null
                && $payload['responded_at'] !== null
                && $payload['url'] === route('advisory.responses.show', [
                    'advisoryRequest' => $advisoryRequest,
                    'advisoryResponse' => $payload['advisory_response_id'],
                ]);
        },
    );

    Notification::assertNotSentTo($unrelatedUser, AdvisoryResponseRecordedNotification::class);
    Notification::assertCount(1);
});

it('stores the expected advisory response notification payload in the database while preserving response creation flow', function (): void {
    $requester = createAdvisoryUserWithRole(SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $teamLeader = createAdvisoryUserWithRole(SystemRole::ADVISORY_TEAM_LEADER, 'LEG', 'ADV');
    $expert = createAdvisoryUserWithRole(SystemRole::LEGAL_EXPERT, 'LEG', 'ADV');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-2026-9302',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Database notification payload',
        'request_type' => 'written',
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => 'expert',
        'priority' => 'medium',
        'director_decision' => 'approved',
        'description' => 'Database notification should contain the response metadata.',
        'date_submitted' => now()->toDateString(),
    ]);

    $this->actingAs($expert)
        ->post(route('advisory.respond', $advisoryRequest), [
            'subject' => 'Response stored in notifications',
            'response' => '<p>Requester can now review the advice.</p>',
        ])
        ->assertRedirect(route('advisory.show', $advisoryRequest))
        ->assertSessionHasNoErrors();

    $advisoryRequest->refresh();
    $notification = $requester->fresh()->notifications()->latest()->first();

    expect($advisoryRequest->status)->toBe(AdvisoryRequestStatus::RESPONDED)
        ->and($advisoryRequest->responses()->count())->toBe(1)
        ->and($notification)->not()->toBeNull()
        ->and($notification->data['type'])->toBe('advisory.response_recorded')
        ->and($notification->data['request_number'])->toBe($advisoryRequest->request_number)
        ->and($notification->data['subject'])->toBe('Database notification payload')
        ->and($notification->data['responder_name'])->toBe($expert->name)
        ->and($notification->data['responded_at'])->not()->toBeNull()
        ->and($notification->data['url'])->toContain('/advisory/');
});

function createAdvisoryUserWithRole(SystemRole $role, string $departmentCode, ?string $teamCode = null): User
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $team = $teamCode !== null ? Team::query()->where('code', $teamCode)->firstOrFail() : null;

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => fake()->unique()->safeEmail(),
    ]);

    $user->assignRole($role->value);

    return $user;
}

<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Jobs\SendSmsMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AppealDeadlineReminderNotification;
use App\Notifications\OverdueRequestNotification;
use App\Notifications\UpcomingAdvisoryDueReminderNotification;
use App\Notifications\UpcomingHearingReminderNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('sends upcoming hearing reminders to the active case chain', function (): void {
    Notification::fake();
    Queue::fake();

    $director = createReminderUser(SystemRole::LEGAL_DIRECTOR, 'director');
    $teamLeader = createReminderUser(SystemRole::LITIGATION_TEAM_LEADER, 'litigation_leader', 'LIT');
    $expert = createReminderUser(SystemRole::LEGAL_EXPERT, 'litigation_expert', 'LIT');

    $legalDepartment = Department::query()->where('code', 'LEG')->firstOrFail();

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-REM-0001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $director->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Employee C',
        'defendant' => $legalDepartment->name_en,
        'bench_or_chamber' => 'Bench 1',
        'status' => CaseStatus::IN_PROGRESS,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'claim_summary' => 'Employment benefits claim.',
        'institution_position' => 'Institution denies liability.',
        'filing_date' => now()->subDays(5)->toDateString(),
        'next_hearing_date' => now()->addDay()->toDateString(),
    ]);

    $this->artisan('legal:send-upcoming-hearing-reminders', ['--days' => 2])
        ->expectsOutput('Processed 1 cases and queued 3 hearing reminder deliveries.')
        ->assertSuccessful();

    Notification::assertSentTo($director, UpcomingHearingReminderNotification::class);
    Notification::assertSentTo($teamLeader, UpcomingHearingReminderNotification::class);
    Notification::assertSentTo($expert, UpcomingHearingReminderNotification::class);
    Queue::assertPushed(SendSmsMessageJob::class, 3);
    Queue::assertPushed(SendTelegramMessageJob::class, 3);

    expect($legalCase->refresh()->next_hearing_date?->toDateString())->toBe(now()->addDay()->toDateString());
});

it('sends overdue advisory reminders to the active advisory chain', function (): void {
    Notification::fake();
    Queue::fake();

    $requester = createReminderUser(SystemRole::DEPARTMENT_REQUESTER, 'requester', null, 'HR');
    $director = createReminderUser(SystemRole::LEGAL_DIRECTOR, 'director');
    $teamLeader = createReminderUser(SystemRole::ADVISORY_TEAM_LEADER, 'advisory_leader', 'ADV');
    $expert = createReminderUser(SystemRole::LEGAL_EXPERT, 'advisory_expert', 'ADV');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-REM-0001',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Urgent employment advisory',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'description' => 'Pending opinion on employee grievance.',
        'date_submitted' => now()->subDays(7)->toDateString(),
        'due_date' => now()->subDay()->toDateString(),
    ]);

    $this->artisan('legal:send-overdue-advisory-reminders')
        ->expectsOutput('Processed 1 advisory requests and queued 4 reminder deliveries.')
        ->assertSuccessful();

    Notification::assertSentTo($requester, OverdueRequestNotification::class);
    Notification::assertSentTo($director, OverdueRequestNotification::class);
    Notification::assertSentTo($teamLeader, OverdueRequestNotification::class);
    Notification::assertSentTo($expert, OverdueRequestNotification::class);
    Queue::assertPushed(SendSmsMessageJob::class, 4);
    Queue::assertPushed(SendTelegramMessageJob::class, 4);

    expect($advisoryRequest->refresh()->due_date?->toDateString())->toBe(now()->subDay()->toDateString());
});

it('sends upcoming advisory due reminders using the configured lead time', function (): void {
    Notification::fake();
    Queue::fake();

    $requester = createReminderUser(SystemRole::DEPARTMENT_REQUESTER, 'requester-upcoming', null, 'HR');
    $director = createReminderUser(SystemRole::LEGAL_DIRECTOR, 'director-upcoming');
    $teamLeader = createReminderUser(SystemRole::ADVISORY_TEAM_LEADER, 'advisory-leader-upcoming', 'ADV');
    $expert = createReminderUser(SystemRole::LEGAL_EXPERT, 'advisory-expert-upcoming', 'ADV');

    AdvisoryRequest::query()->create([
        'request_number' => 'ADV-REM-0002',
        'department_id' => $requester->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $requester->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'subject' => 'Upcoming due reminder',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::MEDIUM,
        'director_decision' => DirectorDecision::APPROVED,
        'description' => 'Reminder before advisory due date.',
        'date_submitted' => now()->subDays(2)->toDateString(),
        'due_date' => now()->addDay()->toDateString(),
    ]);

    $this->artisan('legal:send-overdue-advisory-reminders', ['--days' => 2])
        ->expectsOutput('Processed 1 advisory requests and queued 4 reminder deliveries.')
        ->assertSuccessful();

    Notification::assertSentTo($requester, UpcomingAdvisoryDueReminderNotification::class);
    Notification::assertSentTo($director, UpcomingAdvisoryDueReminderNotification::class);
    Notification::assertSentTo($teamLeader, UpcomingAdvisoryDueReminderNotification::class);
    Notification::assertSentTo($expert, UpcomingAdvisoryDueReminderNotification::class);
    Queue::assertPushed(SendSmsMessageJob::class, 4);
    Queue::assertPushed(SendTelegramMessageJob::class, 4);
});

it('sends appeal deadline reminders to the active case chain', function (): void {
    Notification::fake();
    Queue::fake();

    $director = createReminderUser(SystemRole::LEGAL_DIRECTOR, 'appeal_director');
    $teamLeader = createReminderUser(SystemRole::LITIGATION_TEAM_LEADER, 'appeal_leader', 'LIT');
    $expert = createReminderUser(SystemRole::LEGAL_EXPERT, 'appeal_expert', 'LIT');

    $legalDepartment = Department::query()->where('code', 'LEG')->firstOrFail();

    LegalCase::query()->create([
        'case_number' => 'CASE-APP-0001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $director->id,
        'director_reviewer_id' => $director->id,
        'assigned_team_leader_id' => $teamLeader->id,
        'assigned_legal_expert_id' => $expert->id,
        'plaintiff' => 'Employee D',
        'defendant' => $legalDepartment->name_en,
        'bench_or_chamber' => 'Bench 4',
        'status' => CaseStatus::DECIDED,
        'workflow_stage' => WorkflowStage::EXPERT,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'claim_summary' => 'Appeal deadline reminder case.',
        'institution_position' => 'Institution is evaluating appellate options.',
        'filing_date' => now()->subDays(20)->toDateString(),
        'decision_date' => now()->subDay()->toDateString(),
        'appeal_deadline' => now()->addDays(2)->toDateString(),
    ]);

    $this->artisan('legal:send-appeal-deadline-reminders', ['--days' => 3])
        ->expectsOutput('Processed 1 cases and queued 3 appeal deadline reminder deliveries.')
        ->assertSuccessful();

    Notification::assertSentTo($director, AppealDeadlineReminderNotification::class);
    Notification::assertSentTo($teamLeader, AppealDeadlineReminderNotification::class);
    Notification::assertSentTo($expert, AppealDeadlineReminderNotification::class);
    Queue::assertPushed(SendSmsMessageJob::class, 3);
    Queue::assertPushed(SendTelegramMessageJob::class, 3);
});

function createReminderUser(
    SystemRole $role,
    string $emailPrefix,
    ?string $teamCode = null,
    string $departmentCode = 'LEG',
): User {
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();
    $team = $teamCode !== null ? Team::query()->where('code', $teamCode)->firstOrFail() : null;

    $user = User::factory()->create([
        'department_id' => $department->id,
        'team_id' => $team?->id,
        'email' => "{$emailPrefix}@ldms.test",
        'phone' => '+251911000000',
        'telegram_chat_id' => "{$emailPrefix}-telegram",
    ]);

    $user->assignRole($role->value);

    return $user;
}

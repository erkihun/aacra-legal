<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryAssignment;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\AdvisoryResponse;
use App\Models\CaseAssignment;
use App\Models\CaseHearing;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\LegalCaseType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::query()->where('email', 'director@ldms.test')->exists()) {
            $this->call(DemoUserSeeder::class);
        }

        $hrDepartment = Department::query()->where('code', 'HR')->firstOrFail();

        $users = [
            'director' => User::query()->where('email', 'director@ldms.test')->firstOrFail(),
            'litigation_lead' => User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail(),
            'advisory_lead' => User::query()->where('email', 'advisory.lead@ldms.test')->firstOrFail(),
            'expert_one' => User::query()->where('email', 'expert.one@ldms.test')->firstOrFail(),
            'expert_two' => User::query()->where('email', 'expert.two@ldms.test')->firstOrFail(),
            'requester' => User::query()->where('email', 'requester@ldms.test')->firstOrFail(),
            'registrar' => User::query()->where('email', 'registrar@ldms.test')->firstOrFail(),
        ];

        $advisoryRequest = AdvisoryRequest::query()->updateOrCreate(
            ['request_number' => 'ADV-2026-0001'],
            [
                'department_id' => $hrDepartment->id,
                'category_id' => AdvisoryCategory::query()->where('code', 'HR-POL')->value('id'),
                'requester_user_id' => $users['requester']->id,
                'director_reviewer_id' => $users['director']->id,
                'assigned_team_leader_id' => $users['advisory_lead']->id,
                'assigned_legal_expert_id' => $users['expert_one']->id,
                'subject' => 'Review disciplinary due-process checklist',
                'request_type' => AdvisoryRequestType::WRITTEN,
                'status' => AdvisoryRequestStatus::RESPONDED,
                'workflow_stage' => WorkflowStage::COMPLETED,
                'priority' => PriorityLevel::HIGH,
                'director_decision' => DirectorDecision::APPROVED,
                'description' => 'HR requested legal guidance on the disciplinary due-process checklist before issuing a final decision.',
                'director_notes' => 'Proceed through advisory team leader and return written advice.',
                'internal_summary' => 'Advice delivered with compliance checkpoints.',
                'date_submitted' => now()->subDays(12)->toDateString(),
                'due_date' => now()->subDays(4)->toDateString(),
                'completed_at' => now()->subDays(5),
            ],
        );

        AdvisoryAssignment::query()->updateOrCreate(
            ['advisory_request_id' => $advisoryRequest->id, 'assigned_to_id' => $users['advisory_lead']->id],
            [
                'assigned_by_id' => $users['director']->id,
                'assignment_role' => 'team_leader',
                'notes' => 'Coordinate response and assign to a senior expert.',
                'assigned_at' => now()->subDays(11),
            ],
        );

        AdvisoryAssignment::query()->updateOrCreate(
            ['advisory_request_id' => $advisoryRequest->id, 'assigned_to_id' => $users['expert_one']->id],
            [
                'assigned_by_id' => $users['advisory_lead']->id,
                'assignment_role' => 'expert',
                'notes' => 'Prepare written opinion with procedural safeguards.',
                'assigned_at' => now()->subDays(10),
            ],
        );

        AdvisoryResponse::query()->updateOrCreate(
            ['advisory_request_id' => $advisoryRequest->id, 'responder_id' => $users['expert_one']->id],
            [
                'response_type' => AdvisoryRequestType::WRITTEN,
                'summary' => 'The department may proceed if notice, hearing, and proportionality requirements are met.',
                'advice_text' => 'A written opinion was issued recommending notice, opportunity to respond, documentary record retention, and an appeal clause in the final communication.',
                'follow_up_notes' => 'Schedule a briefing with HR next week.',
                'responded_at' => now()->subDays(5),
            ],
        );

        $legalCase = LegalCase::query()->updateOrCreate(
            ['case_number' => 'CASE-2026-0001'],
            [
                'external_court_file_number' => 'FHC/3381/2018',
                'court_id' => Court::query()->where('code', 'AA-FHC')->value('id'),
                'case_type_id' => LegalCaseType::query()->where('code', 'LAB')->value('id'),
                'registered_by_id' => $users['registrar']->id,
                'director_reviewer_id' => $users['director']->id,
                'assigned_team_leader_id' => $users['litigation_lead']->id,
                'assigned_legal_expert_id' => $users['expert_two']->id,
                'plaintiff' => 'Former Employee A',
                'defendant' => 'Institution',
                'bench_or_chamber' => 'Labor Bench 2',
                'status' => CaseStatus::IN_PROGRESS,
                'workflow_stage' => WorkflowStage::EXPERT,
                'priority' => PriorityLevel::CRITICAL,
                'director_decision' => DirectorDecision::APPROVED,
                'claim_summary' => 'Claim for unlawful termination and unpaid benefits.',
                'institution_position' => 'Termination followed documented disciplinary steps and payroll reconciliation.',
                'outcome' => null,
                'director_notes' => 'Track hearing schedule closely and preserve employment records.',
                'filing_date' => now()->subDays(30)->toDateString(),
                'next_hearing_date' => now()->addDays(9)->toDateString(),
                'decision_date' => null,
                'appeal_deadline' => null,
            ],
        );

        CaseAssignment::query()->updateOrCreate(
            ['legal_case_id' => $legalCase->id, 'assigned_to_id' => $users['litigation_lead']->id],
            [
                'assigned_by_id' => $users['director']->id,
                'assignment_role' => 'team_leader',
                'notes' => 'Handle through litigation team and report after each hearing.',
                'assigned_at' => now()->subDays(28),
            ],
        );

        CaseAssignment::query()->updateOrCreate(
            ['legal_case_id' => $legalCase->id, 'assigned_to_id' => $users['expert_two']->id],
            [
                'assigned_by_id' => $users['litigation_lead']->id,
                'assignment_role' => 'expert',
                'notes' => 'Prepare witness list and payroll exhibits.',
                'assigned_at' => now()->subDays(27),
            ],
        );

        CaseHearing::query()->updateOrCreate(
            ['legal_case_id' => $legalCase->id, 'hearing_date' => now()->subDays(3)->toDateString()],
            [
                'recorded_by_id' => $users['expert_two']->id,
                'next_hearing_date' => now()->addDays(9)->toDateString(),
                'appearance_status' => 'attended',
                'summary' => 'Court admitted documentary evidence and requested payroll clarification.',
                'institution_position' => 'Institution requested time to submit certified payroll summaries.',
                'court_decision' => 'Continued to next hearing for evidence completion.',
                'outcome' => null,
            ],
        );
    }
}

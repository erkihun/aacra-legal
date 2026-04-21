<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Models\AdvisoryRequest;
use App\Models\Complaint;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildDashboardDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $advisoryQuery = AdvisoryRequest::query()->visibleTo($user);
        $caseQuery = LegalCase::query()->visibleTo($user);
        $complaintQuery = $user->canAnyPermissions([
            'complaints.view',
            'complaints.view_all',
            'complaints.view_department',
            'complaints.view_own',
        ])
            ? Complaint::query()->visibleTo($user)
            : Complaint::query()->whereRaw('1 = 0');

        return [
            'role_context' => $this->roleContext($user),
            'requester_summary' => $this->requesterSummary($user),
            'metrics' => [
                'open_cases' => (clone $caseQuery)
                    ->whereNotIn('status', [CaseStatus::CLOSED, CaseStatus::REJECTED])
                    ->count(),
                'upcoming_hearings' => (clone $caseQuery)
                    ->whereBetween('next_hearing_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
                    ->count(),
                'pending_director_approvals' => (clone $advisoryQuery)
                    ->where('status', AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW)
                    ->count()
                    + (clone $caseQuery)
                        ->where('status', CaseStatus::UNDER_DIRECTOR_REVIEW)
                        ->count(),
                'advisory_awaiting_assignment' => (clone $advisoryQuery)
                    ->whereIn('status', [
                        AdvisoryRequestStatus::ASSIGNED_TO_TEAM_LEADER,
                        AdvisoryRequestStatus::ASSIGNED_TO_EXPERT,
                    ])
                    ->count(),
                'overdue_advisory_requests' => (clone $advisoryQuery)
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', [
                        AdvisoryRequestStatus::RESPONDED,
                        AdvisoryRequestStatus::CLOSED,
                        AdvisoryRequestStatus::REJECTED,
                    ])
                    ->count(),
                'judgments_recorded_this_month' => (clone $caseQuery)
                    ->whereMonth('decision_date', now()->month)
                    ->whereYear('decision_date', now()->year)
                    ->count(),
                'closed_matters_this_month' => (clone $caseQuery)
                    ->whereMonth('completed_at', now()->month)
                    ->whereYear('completed_at', now()->year)
                    ->count()
                    + (clone $advisoryQuery)
                        ->whereMonth('completed_at', now()->month)
                        ->whereYear('completed_at', now()->year)
                        ->count(),
                'open_complaints' => (clone $complaintQuery)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'escalated_complaints' => (clone $complaintQuery)
                    ->where('is_escalated', true)
                    ->count(),
                'monthly_completions' => [
                    'advisory' => (clone $advisoryQuery)
                        ->whereMonth('completed_at', now()->month)
                        ->whereYear('completed_at', now()->year)
                        ->count(),
                    'cases' => (clone $caseQuery)
                        ->whereMonth('completed_at', now()->month)
                        ->whereYear('completed_at', now()->year)
                        ->count(),
                    'complaints' => (clone $complaintQuery)
                        ->whereMonth('resolved_at', now()->month)
                        ->whereYear('resolved_at', now()->year)
                        ->count(),
                ],
            ],
            'cases_by_status' => (clone $caseQuery)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn (LegalCase $legalCase) => [
                    'status' => $this->localizedStatus($legalCase->status?->value),
                    'total' => (int) $legalCase->getAttribute('total'),
                ])
                ->values(),
            'work_by_expert' => $this->workloadByExpert($user),
            'recent_advisories' => AdvisoryRequest::query()
                ->visibleTo($user)
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'request_number', 'subject', 'status', 'due_date']),
            'recent_cases' => LegalCase::query()
                ->visibleTo($user)
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'case_number', 'plaintiff', 'status', 'next_hearing_date']),
            'recent_complaints' => Complaint::query()
                ->visibleTo($user)
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'complaint_number', 'subject', 'status', 'department_response_deadline_at']),
            'recently_updated_matters' => $this->recentMatters($user)->all(),
        ];
    }

    /**
     * @return array<string, int>|null
     */
    private function requesterSummary(User $user): ?array
    {
        if (! $user->usesRequesterAdvisoryScope()) {
            return null;
        }

        $query = AdvisoryRequest::query()->visibleTo($user);

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)
                ->whereNotIn('status', [
                    AdvisoryRequestStatus::RESPONDED,
                    AdvisoryRequestStatus::CLOSED,
                    AdvisoryRequestStatus::REJECTED,
                ])
                ->count(),
            'returned' => (clone $query)->where('status', AdvisoryRequestStatus::RETURNED)->count(),
            'completed' => (clone $query)
                ->whereIn('status', [
                    AdvisoryRequestStatus::RESPONDED,
                    AdvisoryRequestStatus::CLOSED,
                ])
                ->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function roleContext(User $user): array
    {
        return match (true) {
            $user->hasCaseAdministrativeAccess() && $user->hasAdvisoryAdministrativeAccess() && $user->can('settings.manage') => [
                'key' => 'super_admin',
                'label' => __('dashboard.role.super_admin'),
                'description' => __('dashboard.role_description.super_admin'),
            ],
            $user->can('audit.view') => [
                'key' => 'auditor',
                'label' => __('dashboard.role.auditor'),
                'description' => __('dashboard.role_description.auditor'),
            ],
            $user->hasAdvisoryAdministrativeAccess() || $user->hasCaseAdministrativeAccess() => [
                'key' => 'legal_director',
                'label' => __('dashboard.role.legal_director'),
                'description' => __('dashboard.role_description.legal_director'),
            ],
            $user->canLeadLitigationWorkflow() => [
                'key' => 'litigation_team_leader',
                'label' => __('dashboard.role.litigation_team_leader'),
                'description' => __('dashboard.role_description.litigation_team_leader'),
            ],
            $user->canLeadAdvisoryWorkflow() => [
                'key' => 'advisory_team_leader',
                'label' => __('dashboard.role.advisory_team_leader'),
                'description' => __('dashboard.role_description.advisory_team_leader'),
            ],
            $user->canRespondToAdvisories() || $user->canHandleAssignedCases() => [
                'key' => 'legal_expert',
                'label' => __('dashboard.role.legal_expert'),
                'description' => __('dashboard.role_description.legal_expert'),
            ],
            $user->usesRequesterAdvisoryScope() => [
                'key' => 'department_requester',
                'label' => __('dashboard.role.department_requester'),
                'description' => __('dashboard.role_description.department_requester'),
            ],
            $user->canRegisterCases() => [
                'key' => 'registrar',
                'label' => __('dashboard.role.registrar'),
                'description' => __('dashboard.role_description.registrar'),
            ],
            default => [
                'key' => 'user',
                'label' => __('common.user'),
                'description' => __('dashboard.description'),
            ],
        };
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function workloadByExpert(User $user): Collection
    {
        $expertQuery = User::query()
            ->where('is_active', true)
            ->withAnyPermission([
                'advisory.respond',
                'advisory-requests.respond',
                'cases.record_hearing',
                'legal-cases.update',
            ]);

        if ($user->canLeadLitigationWorkflow() || $user->canLeadAdvisoryWorkflow()) {
            $expertQuery->where('team_id', $user->team_id);
        }

        if ($user->canHandleAssignedCases() || $user->canRespondToAdvisories()) {
            $expertQuery->whereKey($user->getKey());
        }

        return $expertQuery
            ->get()
            ->map(fn (User $expert) => [
                'id' => $expert->id,
                'name' => $expert->name,
                'advisory' => AdvisoryRequest::query()->where('assigned_legal_expert_id', $expert->getKey())->count(),
                'cases' => LegalCase::query()->where('assigned_legal_expert_id', $expert->getKey())->count(),
            ])
            ->sortByDesc(fn (array $workload) => $workload['advisory'] + $workload['cases'])
            ->values();
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    private function recentMatters(User $user): Collection
    {
        $advisories = AdvisoryRequest::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (AdvisoryRequest $advisoryRequest) => [
                'id' => $advisoryRequest->id,
                'module' => __('modules.advisory'),
                'reference' => $advisoryRequest->request_number,
                'subject' => $advisoryRequest->subject,
                'status' => $advisoryRequest->status?->value,
                'updated_at' => $advisoryRequest->updated_at?->toIso8601String(),
                'route' => route('advisory.show', $advisoryRequest),
            ]);

        $cases = LegalCase::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (LegalCase $legalCase) => [
                'id' => $legalCase->id,
                'module' => __('modules.case'),
                'reference' => $legalCase->case_number,
                'subject' => $this->caseSubject($legalCase->plaintiff, $legalCase->defendant),
                'status' => $legalCase->status?->value,
                'updated_at' => $legalCase->updated_at?->toIso8601String(),
                'route' => route('cases.show', $legalCase),
            ]);

        $complaints = Complaint::query()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'module' => 'Complaints',
                'reference' => $complaint->complaint_number,
                'subject' => $complaint->subject,
                'status' => $complaint->status?->value,
                'updated_at' => $complaint->updated_at?->toIso8601String(),
                'route' => route('complaints.show', $complaint),
            ]);

        return $advisories
            ->concat($cases)
            ->concat($complaints)
            ->sortByDesc('updated_at')
            ->take(8)
            ->values();
    }

    private function localizedStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return __('common.not_available');
        }

        return __("status.{$status}");
    }

    private function caseSubject(string $plaintiff, string $defendant): string
    {
        return "{$plaintiff} ".__('common.versus')." {$defendant}";
    }
}

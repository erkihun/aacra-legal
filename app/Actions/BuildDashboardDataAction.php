<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryRequest;
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
                'monthly_completions' => [
                    'advisory' => (clone $advisoryQuery)
                        ->whereMonth('completed_at', now()->month)
                        ->whereYear('completed_at', now()->year)
                        ->count(),
                    'cases' => (clone $caseQuery)
                        ->whereMonth('completed_at', now()->month)
                        ->whereYear('completed_at', now()->year)
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
            'recently_updated_matters' => $this->recentMatters($user)->all(),
        ];
    }

    /**
     * @return array<string, int>|null
     */
    private function requesterSummary(User $user): ?array
    {
        if (! $user->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER)) {
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
            $user->hasSystemRole(SystemRole::SUPER_ADMIN) => [
                'key' => 'super_admin',
                'label' => __('dashboard.role.super_admin'),
                'description' => __('dashboard.role_description.super_admin'),
            ],
            $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR) => [
                'key' => 'legal_director',
                'label' => __('dashboard.role.legal_director'),
                'description' => __('dashboard.role_description.legal_director'),
            ],
            $user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER) => [
                'key' => 'litigation_team_leader',
                'label' => __('dashboard.role.litigation_team_leader'),
                'description' => __('dashboard.role_description.litigation_team_leader'),
            ],
            $user->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER) => [
                'key' => 'advisory_team_leader',
                'label' => __('dashboard.role.advisory_team_leader'),
                'description' => __('dashboard.role_description.advisory_team_leader'),
            ],
            $user->hasSystemRole(SystemRole::LEGAL_EXPERT) => [
                'key' => 'legal_expert',
                'label' => __('dashboard.role.legal_expert'),
                'description' => __('dashboard.role_description.legal_expert'),
            ],
            $user->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER) => [
                'key' => 'department_requester',
                'label' => __('dashboard.role.department_requester'),
                'description' => __('dashboard.role_description.department_requester'),
            ],
            $user->hasSystemRole(SystemRole::REGISTRAR) => [
                'key' => 'registrar',
                'label' => __('dashboard.role.registrar'),
                'description' => __('dashboard.role_description.registrar'),
            ],
            $user->hasSystemRole(SystemRole::AUDITOR) => [
                'key' => 'auditor',
                'label' => __('dashboard.role.auditor'),
                'description' => __('dashboard.role_description.auditor'),
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
        $expertQuery = User::query()->role(SystemRole::LEGAL_EXPERT->value);

        if ($user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER) || $user->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER)) {
            $expertQuery->where('team_id', $user->team_id);
        }

        if ($user->hasSystemRole(SystemRole::LEGAL_EXPERT)) {
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

        return $advisories
            ->concat($cases)
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

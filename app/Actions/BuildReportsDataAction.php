<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildReportsDataAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(User $user, array $filters = []): array
    {
        $advisoryQuery = $this->applyAdvisoryFilters(AdvisoryRequest::query()->visibleTo($user), $filters);
        $caseQuery = $this->applyCaseFilters(LegalCase::query()->visibleTo($user), $filters);

        $casesByStatus = (clone $caseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn (LegalCase $legalCase) => [
                'status' => $this->localizedStatus($legalCase->status?->value),
                'total' => (int) $legalCase->getAttribute('total'),
            ])
            ->values()
            ->all();

        $advisoryByDepartment = (clone $advisoryQuery)
            ->join('departments', 'departments.id', '=', 'advisory_requests.department_id')
            ->select('departments.id', 'departments.name_en', 'departments.name_am')
            ->selectRaw('COUNT(advisory_requests.id) as total')
            ->groupBy('departments.id', 'departments.name_en', 'departments.name_am')
            ->orderByDesc('total')
            ->get()
            ->map(fn (AdvisoryRequest $advisoryRequest) => [
                'department' => (string) $this->localizedName(
                    $advisoryRequest->getAttribute('name_en'),
                    $advisoryRequest->getAttribute('name_am'),
                ),
                'total' => (int) $advisoryRequest->getAttribute('total'),
            ])
            ->values()
            ->all();

        $expertWorkload = User::query()
            ->role(SystemRole::LEGAL_EXPERT->value)
            ->orderBy('name')
            ->get()
            ->map(function (User $expert) use ($advisoryQuery, $caseQuery): array {
                $advisoryCount = (clone $advisoryQuery)
                    ->where('assigned_legal_expert_id', $expert->getKey())
                    ->whereNotIn('status', [
                        AdvisoryRequestStatus::RESPONDED,
                        AdvisoryRequestStatus::CLOSED,
                        AdvisoryRequestStatus::REJECTED,
                    ])
                    ->count();

                $caseCount = (clone $caseQuery)
                    ->where('assigned_legal_expert_id', $expert->getKey())
                    ->whereNotIn('status', [CaseStatus::CLOSED, CaseStatus::REJECTED])
                    ->count();

                return [
                    'expert' => $expert->name,
                    'advisory' => $advisoryCount,
                    'cases' => $caseCount,
                    'total' => $advisoryCount + $caseCount,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $turnaroundRows = $this->buildTurnaroundRows($user, $filters);
        $hearingSchedule = $this->hearingScheduleRows($user, $filters);
        $overdueItems = $this->overdueItemsRows($user, $filters);

        return [
            'filters' => $filters,
            'cases_by_status' => $casesByStatus,
            'advisory_by_department' => $advisoryByDepartment,
            'expert_workload' => $expertWorkload,
            'turnaround' => [
                'average_advisory_days' => $this->averageTurnaroundDays($turnaroundRows->where('module_key', 'advisory')),
                'average_case_days' => $this->averageTurnaroundDays($turnaroundRows->where('module_key', 'case')),
                'rows' => $turnaroundRows->values()->all(),
            ],
            'hearing_schedule' => $hearingSchedule->all(),
            'overdue_items' => $overdueItems->all(),
            'filter_options' => [
                'departments' => Department::query()->orderBy('name_en')->get(['id', 'name_en', 'name_am'])->map(fn (Department $department) => [
                    'value' => $department->id,
                    'label' => $this->localizedName($department->name_en, $department->name_am),
                ]),
                'teams' => Team::query()->orderBy('name_en')->get(['id', 'name_en', 'name_am'])->map(fn (Team $team) => [
                    'value' => $team->id,
                    'label' => $this->localizedName($team->name_en, $team->name_am),
                ]),
                'experts' => User::query()->role(SystemRole::LEGAL_EXPERT->value)->orderBy('name')->get(['id', 'name'])->map(fn (User $expert) => [
                    'value' => $expert->id,
                    'label' => $expert->name,
                ]),
            ],
        ];
    }

    /**
     * @return Collection<int, array<string, int|string|null>>
     */
    private function buildTurnaroundRows(User $user, array $filters): Collection
    {
        $advisoryRows = $this->applyAdvisoryFilters(AdvisoryRequest::query()->visibleTo($user), $filters)
            ->whereNotNull('completed_at')
            ->get(['request_number', 'subject', 'date_submitted', 'completed_at'])
            ->map(fn (AdvisoryRequest $advisoryRequest) => [
                'module_key' => 'advisory',
                'module' => __('modules.advisory'),
                'reference' => $advisoryRequest->request_number,
                'subject' => $advisoryRequest->subject,
                'opened_at' => $advisoryRequest->date_submitted?->toDateString(),
                'completed_at' => $advisoryRequest->completed_at?->toDateString(),
                'turnaround_days' => $advisoryRequest->date_submitted?->diffInDays($advisoryRequest->completed_at) ?? 0,
            ]);

        $caseRows = $this->applyCaseFilters(LegalCase::query()->visibleTo($user), $filters)
            ->whereNotNull('completed_at')
            ->get(['case_number', 'plaintiff', 'filing_date', 'completed_at'])
            ->map(fn (LegalCase $legalCase) => [
                'module_key' => 'case',
                'module' => __('modules.case'),
                'reference' => $legalCase->case_number,
                'subject' => $legalCase->plaintiff,
                'opened_at' => $legalCase->filing_date?->toDateString(),
                'completed_at' => $legalCase->completed_at?->toDateString(),
                'turnaround_days' => $legalCase->filing_date?->diffInDays($legalCase->completed_at) ?? 0,
            ]);

        return $advisoryRows
            ->concat($caseRows)
            ->sortByDesc('turnaround_days')
            ->values();
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    private function hearingScheduleRows(User $user, array $filters): Collection
    {
        return $this->applyCaseFilters(LegalCase::query()->visibleTo($user)->with(['court', 'assignedLegalExpert']), $filters)
            ->whereNotNull('next_hearing_date')
            ->orderBy('next_hearing_date')
            ->get()
            ->map(fn (LegalCase $legalCase) => [
                'case_number' => $legalCase->case_number,
                'plaintiff' => $legalCase->plaintiff,
                'court' => $this->localizedName($legalCase->court?->name_en, $legalCase->court?->name_am),
                'assigned_expert' => $legalCase->assignedLegalExpert?->name,
                'next_hearing_date' => $legalCase->next_hearing_date?->toDateString(),
                'status' => $this->localizedStatus($legalCase->status?->value),
            ]);
    }

    /**
     * @return Collection<int, array<string, string|null>>
     */
    private function overdueItemsRows(User $user, array $filters): Collection
    {
        $advisories = $this->applyAdvisoryFilters(AdvisoryRequest::query()->visibleTo($user)->with(['assignedLegalExpert', 'requester']), $filters)
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', [
                AdvisoryRequestStatus::RESPONDED,
                AdvisoryRequestStatus::CLOSED,
                AdvisoryRequestStatus::REJECTED,
            ])
            ->get()
            ->map(fn (AdvisoryRequest $advisoryRequest) => [
                'module' => __('modules.advisory'),
                'reference' => $advisoryRequest->request_number,
                'subject' => $advisoryRequest->subject,
                'owner' => $advisoryRequest->assignedLegalExpert?->name ?? $advisoryRequest->requester?->name,
                'due_date' => $advisoryRequest->due_date?->toDateString(),
                'status' => $this->localizedStatus($advisoryRequest->status?->value),
            ]);

        $appeals = $this->applyCaseFilters(LegalCase::query()->visibleTo($user)->with('assignedLegalExpert'), $filters)
            ->whereDate('appeal_deadline', '<', now()->toDateString())
            ->whereNotIn('status', [CaseStatus::CLOSED, CaseStatus::REJECTED])
            ->get()
            ->map(fn (LegalCase $legalCase) => [
                'module' => __('modules.case'),
                'reference' => $legalCase->case_number,
                'subject' => $this->caseSubject($legalCase->plaintiff, $legalCase->defendant),
                'owner' => $legalCase->assignedLegalExpert?->name,
                'due_date' => $legalCase->appeal_deadline?->toDateString(),
                'status' => $this->localizedStatus($legalCase->status?->value),
            ]);

        return $advisories->concat($appeals)->sortBy('due_date')->values();
    }

    /**
     * @param  Collection<int, array<string, int|string|null>>  $rows
     */
    private function averageTurnaroundDays(Collection $rows): int
    {
        if ($rows->isEmpty()) {
            return 0;
        }

        return (int) round((float) $rows->avg('turnaround_days'));
    }

    private function localizedName(?string $english, ?string $amharic): ?string
    {
        return app()->getLocale() === 'am'
            ? ($amharic ?: $english)
            : ($english ?: $amharic);
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

    private function applyAdvisoryFilters($query, array $filters)
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($builder, string $date) => $builder->whereDate('date_submitted', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($builder, string $date) => $builder->whereDate('date_submitted', '<=', $date))
            ->when($filters['department_id'] ?? null, fn ($builder, string $departmentId) => $builder->where('department_id', $departmentId))
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($builder, string $priority) => $builder->where('priority', $priority))
            ->when($filters['expert_id'] ?? null, fn ($builder, string $expertId) => $builder->where('assigned_legal_expert_id', $expertId))
            ->when($filters['team_id'] ?? null, function ($builder, string $teamId): void {
                $builder->where(function ($nested) use ($teamId): void {
                    $nested
                        ->whereHas('assignedLegalExpert', fn ($expertQuery) => $expertQuery->where('team_id', $teamId))
                        ->orWhereHas('assignedTeamLeader', fn ($leaderQuery) => $leaderQuery->where('team_id', $teamId));
                });
            });
    }

    private function applyCaseFilters($query, array $filters)
    {
        return $query
            ->when($filters['date_from'] ?? null, fn ($builder, string $date) => $builder->whereDate('filing_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($builder, string $date) => $builder->whereDate('filing_date', '<=', $date))
            ->when($filters['department_id'] ?? null, function ($builder, string $departmentId): void {
                $builder->whereHas('registeredBy', fn ($registeredByQuery) => $registeredByQuery->where('department_id', $departmentId));
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($builder, string $priority) => $builder->where('priority', $priority))
            ->when($filters['expert_id'] ?? null, fn ($builder, string $expertId) => $builder->where('assigned_legal_expert_id', $expertId))
            ->when($filters['team_id'] ?? null, function ($builder, string $teamId): void {
                $builder->where(function ($nested) use ($teamId): void {
                    $nested
                        ->whereHas('assignedLegalExpert', fn ($expertQuery) => $expertQuery->where('team_id', $teamId))
                        ->orWhereHas('assignedTeamLeader', fn ($leaderQuery) => $leaderQuery->where('team_id', $teamId));
                });
            });
    }
}

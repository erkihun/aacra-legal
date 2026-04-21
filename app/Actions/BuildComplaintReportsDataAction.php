<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintStatus;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildComplaintReportsDataAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(User $user, array $filters = []): array
    {
        $query = $this->applyFilters(Complaint::query()->with(['branch', 'department', 'complainant'])->visibleTo($user), $filters);

        return [
            'filters' => $filters,
            'metrics' => [
                'total' => (clone $query)->count(),
                'open' => (clone $query)->whereNotIn('status', [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED])->count(),
                'overdue' => (clone $query)->where('is_overdue', true)->count(),
                'escalated' => (clone $query)->where('is_escalated', true)->count(),
                'committee_decided' => (clone $query)->whereNotNull('committee_decision_at')->count(),
                'resolved' => (clone $query)->where('status', ComplaintStatus::RESOLVED)->count(),
            ],
            'by_status' => (clone $query)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'status' => __("status.{$complaint->getAttribute('status')}"),
                    'total' => (int) $complaint->getAttribute('total'),
                ])
                ->values()
                ->all(),
            'by_department' => (clone $query)
                ->join('departments', 'departments.id', '=', 'complaints.department_id')
                ->select('departments.name_en', 'departments.name_am')
                ->selectRaw('COUNT(complaints.id) as total')
                ->groupBy('departments.name_en', 'departments.name_am')
                ->orderByDesc('total')
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'label' => app()->getLocale() === 'am'
                        ? ($complaint->getAttribute('name_am') ?: $complaint->getAttribute('name_en'))
                        : ($complaint->getAttribute('name_en') ?: $complaint->getAttribute('name_am')),
                    'total' => (int) $complaint->getAttribute('total'),
                ])
                ->values()
                ->all(),
            'by_branch' => (clone $query)
                ->leftJoin('branches', 'branches.id', '=', 'complaints.branch_id')
                ->select('branches.name_en', 'branches.name_am')
                ->selectRaw('COUNT(complaints.id) as total')
                ->groupBy('branches.name_en', 'branches.name_am')
                ->orderByDesc('total')
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'label' => app()->getLocale() === 'am'
                        ? ($complaint->getAttribute('name_am') ?: $complaint->getAttribute('name_en') ?: __('common.not_available'))
                        : ($complaint->getAttribute('name_en') ?: $complaint->getAttribute('name_am') ?: __('common.not_available')),
                    'total' => (int) $complaint->getAttribute('total'),
                ])
                ->values()
                ->all(),
            'by_complainant_type' => (clone $query)
                ->selectRaw('complainant_type, COUNT(*) as total')
                ->groupBy('complainant_type')
                ->orderBy('complainant_type')
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'label' => __("complaints.complainant_types.{$complaint->getAttribute('complainant_type')}"),
                    'total' => (int) $complaint->getAttribute('total'),
                ])
                ->values()
                ->all(),
            'rows' => (clone $query)
                ->latest('submitted_at')
                ->limit(100)
                ->get()
                ->map(fn (Complaint $complaint) => [
                    'complaint_number' => $complaint->complaint_number,
                    'subject' => $complaint->subject,
                    'complainant' => $complaint->complainant_name,
                    'branch' => app()->getLocale() === 'am'
                        ? ($complaint->branch?->name_am ?: $complaint->branch?->name_en)
                        : ($complaint->branch?->name_en ?: $complaint->branch?->name_am),
                    'department' => app()->getLocale() === 'am'
                        ? ($complaint->department?->name_am ?: $complaint->department?->name_en)
                        : ($complaint->department?->name_en ?: $complaint->department?->name_am),
                    'complainant_type' => $complaint->complainant_type?->value,
                    'status' => $complaint->status?->value,
                    'submitted_at' => $complaint->submitted_at?->toDateString(),
                    'deadline' => $complaint->department_response_deadline_at?->toDateString(),
                ])
                ->all(),
            'filterOptions' => [
                'branches' => Branch::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am'])->map(fn (Branch $branch) => [
                    'value' => $branch->id,
                    'label' => app()->getLocale() === 'am' ? ($branch->name_am ?: $branch->name_en) : ($branch->name_en ?: $branch->name_am),
                ])->values(),
                'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am'])->map(fn (Department $department) => [
                    'value' => $department->id,
                    'label' => app()->getLocale() === 'am' ? ($department->name_am ?: $department->name_en) : ($department->name_en ?: $department->name_am),
                ])->values(),
            ],
        ];
    }

    private function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($builder, string $search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested
                        ->where('complaint_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('complainant_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status))
            ->when($filters['branch_id'] ?? null, fn ($builder, string $branchId) => $builder->where('branch_id', $branchId))
            ->when($filters['department_id'] ?? null, fn ($builder, string $departmentId) => $builder->where('department_id', $departmentId))
            ->when($filters['complainant_type'] ?? null, fn ($builder, string $complainantType) => $builder->where('complainant_type', $complainantType))
            ->when($filters['date_from'] ?? null, fn ($builder, string $dateFrom) => $builder->whereDate('submitted_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($builder, string $dateTo) => $builder->whereDate('submitted_at', '<=', $dateTo));
    }
}

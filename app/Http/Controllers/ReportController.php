<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildReportsDataAction;
use App\Exports\AdvisoryByDepartmentExport;
use App\Exports\CasesByStatusExport;
use App\Exports\ExpertWorkloadExport;
use App\Exports\HearingScheduleExport;
use App\Exports\OverdueItemsExport;
use App\Exports\TurnaroundTimesExport;
use App\Http\Requests\ReportFilterRequest;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request, BuildReportsDataAction $action): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return Inertia::render('Reports/Index', $action->execute($request->user(), $request->validated()));
    }

    public function export(ReportFilterRequest $request, string $reportType, BuildReportsDataAction $action): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        $reports = $action->execute($request->user(), $request->validated());

        return match ($reportType) {
            'cases-by-status' => Excel::download(
                new CasesByStatusExport($reports['cases_by_status']),
                'cases-by-status.xlsx',
            ),
            'advisory-by-department' => Excel::download(
                new AdvisoryByDepartmentExport($reports['advisory_by_department']),
                'advisory-by-department.xlsx',
            ),
            'expert-workload' => Excel::download(
                new ExpertWorkloadExport($reports['expert_workload']),
                'expert-workload.xlsx',
            ),
            'turnaround-times' => Excel::download(
                new TurnaroundTimesExport($reports['turnaround']['rows']),
                'turnaround-times.xlsx',
            ),
            'hearing-schedule' => Excel::download(
                new HearingScheduleExport($reports['hearing_schedule']),
                'hearing-schedule.xlsx',
            ),
            'overdue-items' => Excel::download(
                new OverdueItemsExport($reports['overdue_items']),
                'overdue-items.xlsx',
            ),
            default => abort(404),
        };
    }
}

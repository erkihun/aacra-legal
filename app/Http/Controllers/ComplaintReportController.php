<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildComplaintReportsDataAction;
use App\Http\Requests\Complaints\ComplaintFilterRequest;
use App\Models\Complaint;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintReportController extends Controller
{
    public function index(ComplaintFilterRequest $request, BuildComplaintReportsDataAction $action): Response
    {
        abort_unless($request->user()?->can('complaints.reports.view') ?? false, 403);

        return Inertia::render('Complaints/Reports/Index', $action->execute(
            $request->user(),
            $request->validated(),
        ));
    }
}

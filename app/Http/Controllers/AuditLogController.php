<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildAuditTimelineDataAction;
use App\Http\Requests\AuditLogFilterRequest;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(AuditLogFilterRequest $request, BuildAuditTimelineDataAction $action): Response
    {
        return Inertia::render('AuditLogs/Index', $action->execute($request->user(), $request->validated()));
    }
}

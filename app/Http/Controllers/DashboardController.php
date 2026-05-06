<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildDashboardDataAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildDashboardDataAction $action): Response
    {
        abort_unless($request->user()?->can('dashboard.view') ?? false, 403);

        return Inertia::render('Dashboard', $action->execute($request->user()));
    }
}

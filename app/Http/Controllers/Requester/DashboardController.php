<?php

declare(strict_types=1);

namespace App\Http\Controllers\Requester;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\LawsuitRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AdvisoryRequest;
use App\Models\LawsuitFilingRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $requester = $request->user('requester');

        $advisoryStats = AdvisoryRequest::query()
            ->where('requester_account_id', $requester->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $lawsuitStats = LawsuitFilingRequest::query()
            ->where('requester_account_id', $requester->getKey())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentAdvisory = AdvisoryRequest::query()
            ->where('requester_account_id', $requester->getKey())
            ->with('category')
            ->latest('date_submitted')
            ->limit(5)
            ->get(['id', 'request_number', 'subject', 'status', 'date_submitted', 'category_id']);

        $recentLawsuit = LawsuitFilingRequest::query()
            ->where('requester_account_id', $requester->getKey())
            ->latest('date_submitted')
            ->limit(5)
            ->get(['id', 'request_code', 'subject', 'status', 'date_submitted']);

        return Inertia::render('Requester/Dashboard', [
            'requester' => [
                'id' => $requester->id,
                'full_name' => $requester->full_name,
                'email' => $requester->email,
                'department' => [
                    'name_en' => $requester->department?->name_en,
                    'name_am' => $requester->department?->name_am,
                ],
            ],
            'stats' => [
                'advisory' => [
                    'total' => $advisoryStats->sum(),
                    'pending' => $advisoryStats->only([
                        AdvisoryRequestStatus::SUBMITTED->value,
                        AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW->value,
                    ])->sum(),
                    'completed' => $advisoryStats->get(AdvisoryRequestStatus::RESPONDED->value, 0),
                ],
                'lawsuit' => [
                    'total' => $lawsuitStats->sum(),
                    'pending' => $lawsuitStats->only([
                        LawsuitRequestStatus::SUBMITTED->value,
                        LawsuitRequestStatus::UNDER_REVIEW->value,
                    ])->sum(),
                    'approved' => $lawsuitStats->get(LawsuitRequestStatus::APPROVED->value, 0),
                ],
            ],
            'recentAdvisory' => $recentAdvisory->map(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'subject' => $r->subject,
                'status' => $r->status?->value,
                'date_submitted' => $r->date_submitted?->toDateString(),
            ]),
            'recentLawsuit' => $recentLawsuit->map(fn ($r) => [
                'id' => $r->id,
                'request_code' => $r->request_code,
                'subject' => $r->subject,
                'status' => $r->status?->value,
                'date_submitted' => $r->date_submitted?->toDateString(),
            ]),
        ]);
    }
}

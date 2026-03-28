<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddCommentAction;
use App\Actions\AssignCaseToExpertAction;
use App\Actions\CloseCaseAction;
use App\Actions\DirectorReviewCaseAction;
use App\Actions\OpenLegalCaseAction;
use App\Actions\RecordCaseHearingAction;
use App\Actions\StoreAttachmentAction;
use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Http\Requests\Cases\AssignLegalCaseRequest;
use App\Http\Requests\Cases\CloseLegalCaseRequest;
use App\Http\Requests\Cases\RecordCaseHearingRequest;
use App\Http\Requests\Cases\ReviewLegalCaseRequest;
use App\Http\Requests\Cases\StoreLegalCaseRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\LegalCaseResource;
use App\Models\Attachment;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LegalCaseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LegalCase::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(CaseStatus::cases(), 'value'))],
        ]);

        $cases = LegalCase::query()
            ->with(['court', 'caseType', 'registeredBy', 'assignedTeamLeader', 'assignedLegalExpert'])
            ->visibleTo($request->user())
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('case_number', 'like', "%{$search}%")
                        ->orWhere('external_court_file_number', 'like', "%{$search}%")
                        ->orWhere('plaintiff', 'like', "%{$search}%")
                        ->orWhere('defendant', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Cases/Index', [
            'filters' => $filters,
            'cases' => LegalCaseResource::collection($cases),
            'can' => [
                'create' => $request->user()?->can('create', LegalCase::class) ?? false,
            ],
            'statusOptions' => collect(CaseStatus::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LegalCase::class);

        return Inertia::render('Cases/Create', [
            'courts' => Court::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'caseTypes' => CaseType::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ]);
    }

    public function store(StoreLegalCaseRequest $request, OpenLegalCaseAction $action): RedirectResponse
    {
        $legalCase = $action->execute($request->validated(), $request->user());

        return to_route('cases.show', $legalCase)->with('success', __('Legal case registered successfully.'));
    }

    public function show(LegalCase $legalCase): Response
    {
        $this->authorize('view', $legalCase);

        $legalCase->load([
            'court',
            'caseType',
            'registeredBy',
            'directorReviewer',
            'assignedTeamLeader',
            'assignedLegalExpert',
            'assignments.assignedBy',
            'assignments.assignedTo',
            'hearings.recordedBy',
            'comments.user',
            'attachments.uploadedBy',
            'activities.causer',
        ]);

        return Inertia::render('Cases/Show', [
            'caseItem' => LegalCaseResource::make($legalCase),
            'teamLeaders' => User::query()
                ->role(SystemRole::LITIGATION_TEAM_LEADER->value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'experts' => User::query()
                ->role(SystemRole::LEGAL_EXPERT->value)
                ->where('is_active', true)
                ->where('team_id', Team::query()->where('code', 'LIT')->value('id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'can' => [
                'review' => request()->user()?->can('review', $legalCase) ?? false,
                'assign' => request()->user()?->can('assign', $legalCase) ?? false,
                'recordHearing' => request()->user()?->can('recordHearing', $legalCase) ?? false,
                'close' => request()->user()?->can('close', $legalCase) ?? false,
                'comment' => request()->user()?->can('comment', $legalCase) ?? false,
                'attach' => request()->user()?->can('attach', $legalCase) ?? false,
            ],
        ]);
    }

    public function review(ReviewLegalCaseRequest $request, LegalCase $legalCase, DirectorReviewCaseAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated(), $request->user());

        return back()->with('success', __('Director case review recorded.'));
    }

    public function assign(AssignLegalCaseRequest $request, LegalCase $legalCase, AssignCaseToExpertAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated(), $request->user());

        return back()->with('success', __('Legal case assigned to expert.'));
    }

    public function recordHearing(RecordCaseHearingRequest $request, LegalCase $legalCase, RecordCaseHearingAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated(), $request->user());

        return back()->with('success', __('Case hearing recorded.'));
    }

    public function close(CloseLegalCaseRequest $request, LegalCase $legalCase, CloseCaseAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated());

        return back()->with('success', __('Legal case closed.'));
    }

    public function addComment(StoreCommentRequest $request, LegalCase $legalCase, AddCommentAction $action): RedirectResponse
    {
        $this->authorize('comment', $legalCase);

        $action->execute(
            $legalCase,
            $request->user(),
            $request->string('body')->toString(),
            (bool) $request->boolean('is_internal', true),
        );

        return back()->with('success', __('Comment added.'));
    }

    public function addAttachment(StoreAttachmentRequest $request, LegalCase $legalCase, StoreAttachmentAction $action): RedirectResponse
    {
        $this->authorize('create', Attachment::class);
        $this->authorize('attach', $legalCase);

        $action->execute($legalCase, $request->file('attachments'), $request->user());

        return back()->with('success', __('Attachment uploaded.'));
    }
}

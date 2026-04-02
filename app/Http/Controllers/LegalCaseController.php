<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddCommentAction;
use App\Actions\AssignCaseToExpertAction;
use App\Actions\CloseCaseAction;
use App\Actions\DeleteLegalCaseAction;
use App\Actions\DirectorReviewCaseAction;
use App\Actions\OpenLegalCaseAction;
use App\Actions\ReopenCaseAction;
use App\Actions\RecordCaseHearingAction;
use App\Actions\StoreAttachmentAction;
use App\Actions\UpdateLegalCaseAction;
use App\Enums\CaseStatus;
use App\Enums\LegalCaseMainType;
use App\Enums\PriorityLevel;
use App\Enums\WorkflowStage;
use App\Http\Requests\Cases\AssignLegalCaseRequest;
use App\Http\Requests\Cases\CloseLegalCaseRequest;
use App\Http\Requests\Cases\RecordCaseHearingRequest;
use App\Http\Requests\Cases\ReopenLegalCaseRequest;
use App\Http\Requests\Cases\ReviewLegalCaseRequest;
use App\Http\Requests\Cases\StoreLegalCaseRequest;
use App\Http\Requests\Cases\UpdateCaseHearingRequest;
use App\Http\Requests\Cases\UpdateLegalCaseRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\LegalCaseResource;
use App\Models\Attachment;
use App\Models\CaseHearing;
use App\Models\CaseType;
use App\Models\Comment;
use App\Models\Court;
use App\Models\LegalCase;
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
            'main_case_type' => ['nullable', Rule::in(array_column(LegalCaseMainType::cases(), 'value'))],
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
                        ->orWhere('defendant', 'like', "%{$search}%")
                        ->orWhere('police_station', 'like', "%{$search}%")
                        ->orWhere('stolen_property_type', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['main_case_type'] ?? null, fn ($query, string $mainCaseType) => $query->where('main_case_type', $mainCaseType))
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
            'mainCaseTypeOptions' => collect(LegalCaseMainType::cases())->map(fn (LegalCaseMainType $type) => [
                'label' => __("cases.main_case_type.{$type->value}"),
                'value' => $type->value,
            ]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LegalCase::class);

        return Inertia::render('Cases/Create', $this->caseFormPayload());
    }

    public function store(StoreLegalCaseRequest $request, OpenLegalCaseAction $action): RedirectResponse
    {
        $legalCase = $action->execute($request->validated(), $request->user());

        return to_route('cases.show', $legalCase)->with('success', __('Legal case registered successfully.'));
    }

    public function edit(LegalCase $legalCase): Response
    {
        $this->authorize('update', $legalCase);

        $legalCase->load(['court', 'caseType', 'attachments.uploadedBy']);

        return Inertia::render('Cases/Create', [
            ...$this->caseFormPayload(),
            'mode' => 'edit',
            'caseItem' => LegalCaseResource::make($legalCase)->resolve(),
        ]);
    }

    public function update(
        UpdateLegalCaseRequest $request,
        LegalCase $legalCase,
        UpdateLegalCaseAction $action,
    ): RedirectResponse {
        $action->execute($legalCase, $request->validated(), $request->user());

        return to_route('cases.show', $legalCase)->with('success', __('Legal case updated successfully.'));
    }

    public function destroy(LegalCase $legalCase, DeleteLegalCaseAction $action): RedirectResponse
    {
        $this->authorize('delete', $legalCase);

        $action->execute($legalCase);

        return back()->with('success', __('Legal case deleted successfully.'));
    }

    public function show(LegalCase $legalCase): Response
    {
        $this->authorize('view', $legalCase);
        $user = request()->user();
        $canReview = $user?->can('review', $legalCase) ?? false;
        $canAssign = $user?->can('assign', $legalCase) ?? false;

        $legalCase->load([
            'court',
            'caseType',
            'registeredBy',
            'directorReviewer',
            'assignedTeamLeader',
            'assignedLegalExpert',
            'reopenedBy',
            'assignments.assignedBy',
            'assignments.assignedTo',
            'hearings.recordedBy',
            'comments.user',
            'attachments.uploadedBy',
            'activities.causer',
        ]);

        return Inertia::render('Cases/Show', [
            'caseItem' => LegalCaseResource::make($legalCase)->resolve(),
            'teamLeaders' => User::query()
                ->eligibleLitigationTeamLeaders()
                ->orderBy('name')
                ->get(['id', 'name']),
            'experts' => User::query()
                ->eligibleLitigationExperts()
                ->orderBy('name')
                ->get(['id', 'name']),
            'can' => [
                'review' => $canReview,
                'assign' => $canAssign,
                'recordHearing' => request()->user()?->can('recordHearing', $legalCase) ?? false,
                'close' => request()->user()?->can('close', $legalCase) ?? false,
                'reopen' => request()->user()?->can('reopen', $legalCase) ?? false,
                'comment' => request()->user()?->can('comment', $legalCase) ?? false,
                'attach' => request()->user()?->can('attach', $legalCase) ?? false,
            ],
            'workspace' => [
                'canAssignTeamLeader' => $canReview
                    && ! $legalCase->isClosed()
                    && $legalCase->assigned_team_leader_id === null
                    && in_array($legalCase->status, [CaseStatus::UNDER_DIRECTOR_REVIEW, CaseStatus::INTAKE], true)
                    && $legalCase->workflow_stage === WorkflowStage::DIRECTOR,
                'canAssignExpert' => $canAssign
                    && ! $legalCase->isClosed()
                    && $legalCase->assigned_team_leader_id !== null
                    && (
                        $user?->isSuperAdmin()
                        || $legalCase->assigned_team_leader_id === $user?->getKey()
                    )
                    && $legalCase->assigned_legal_expert_id === null
                    && $legalCase->status === CaseStatus::ASSIGNED_TO_TEAM_LEADER
                    && $legalCase->workflow_stage === WorkflowStage::TEAM_LEADER,
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

    public function updateHearing(UpdateCaseHearingRequest $request, LegalCase $legalCase, CaseHearing $hearing): RedirectResponse
    {
        abort_unless($hearing->legal_case_id === $legalCase->id, 404);
        $this->authorize('update', $hearing);

        $hearing->update($request->validated());

        $legalCase->update([
            'next_hearing_date' => $request->validated('next_hearing_date'),
        ]);

        return back()->with('success', __('Case hearing updated.'));
    }

    public function destroyHearing(LegalCase $legalCase, CaseHearing $hearing): RedirectResponse
    {
        abort_unless($hearing->legal_case_id === $legalCase->id, 404);
        $this->authorize('delete', $hearing);

        $hearing->delete();

        return back()->with('success', __('Case hearing deleted.'));
    }

    public function close(CloseLegalCaseRequest $request, LegalCase $legalCase, CloseCaseAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated());

        return back()->with('success', __('Legal case closed.'));
    }

    public function reopen(ReopenLegalCaseRequest $request, LegalCase $legalCase, ReopenCaseAction $action): RedirectResponse
    {
        $action->execute($legalCase, $request->validated(), $request->user());

        return back()->with('success', __('Legal case reopened.'));
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

    public function updateComment(UpdateCommentRequest $request, LegalCase $legalCase, Comment $comment): RedirectResponse
    {
        abort_unless($comment->commentable instanceof LegalCase && $comment->commentable->is($legalCase), 404);
        $this->authorize('update', $comment);

        $comment->update([
            'body' => $request->string('body')->trim()->toString(),
        ]);

        return back()->with('success', __('Comment updated.'));
    }

    public function destroyComment(LegalCase $legalCase, Comment $comment): RedirectResponse
    {
        abort_unless($comment->commentable instanceof LegalCase && $comment->commentable->is($legalCase), 404);
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', __('Comment deleted.'));
    }

    public function addAttachment(StoreAttachmentRequest $request, LegalCase $legalCase, StoreAttachmentAction $action): RedirectResponse
    {
        $this->authorize('create', Attachment::class);
        $this->authorize('attach', $legalCase);

        $action->execute($legalCase, $request->file('attachments'), $request->user());

        return back()->with('success', __('Attachment uploaded.'));
    }

    private function caseFormPayload(): array
    {
        return [
            'courts' => Court::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'caseTypes' => CaseType::query()
                ->where('is_active', true)
                ->orderBy('name_en')
                ->get(['id', 'code', 'name_en', 'name_am'])
                ->map(fn (CaseType $caseType) => [
                    'id' => $caseType->id,
                    'code' => $caseType->code,
                    'name_en' => $caseType->name_en,
                    'name_am' => $caseType->name_am,
                    'main_case_type' => LegalCaseMainType::inferFromCaseTypeCode($caseType->code)->value,
                ]),
            'mainCaseTypeOptions' => collect(LegalCaseMainType::cases())->map(fn (LegalCaseMainType $type) => [
                'label' => __("cases.main_case_type.{$type->value}"),
                'value' => $type->value,
            ]),
            'statusOptions' => collect([CaseStatus::INTAKE, CaseStatus::UNDER_DIRECTOR_REVIEW])->map(fn (CaseStatus $status) => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ]),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ];
    }
}

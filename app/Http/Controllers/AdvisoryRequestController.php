<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AddCommentAction;
use App\Actions\AssignAdvisoryToExpertAction;
use App\Actions\DirectorReviewAdvisoryAction;
use App\Actions\RecordAdvisoryResponseAction;
use App\Actions\StoreAttachmentAction;
use App\Actions\SubmitAdvisoryRequestAction;
use App\Actions\UpdateReturnedAdvisoryRequestAction;
use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Http\Requests\Advisory\AssignAdvisoryRequestRequest;
use App\Http\Requests\Advisory\RecordAdvisoryResponseRequest;
use App\Http\Requests\Advisory\ReviewAdvisoryRequestRequest;
use App\Http\Requests\Advisory\StoreAdvisoryRequestRequest;
use App\Http\Requests\Advisory\UpdateAdvisoryRequestRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\AdvisoryRequestResource;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AdvisoryRequest::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(AdvisoryRequestStatus::cases(), 'value'))],
            'request_type' => ['nullable', Rule::in(array_column(AdvisoryRequestType::cases(), 'value'))],
        ]);

        $advisoryRequests = AdvisoryRequest::query()
            ->with(['department', 'category', 'requester', 'assignedTeamLeader', 'assignedLegalExpert'])
            ->visibleTo($request->user())
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('request_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['request_type'] ?? null, fn ($query, string $type) => $query->where('request_type', $type))
            ->latest('date_submitted')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Advisory/Index', [
            'filters' => $filters,
            'requests' => AdvisoryRequestResource::collection($advisoryRequests),
            'can' => [
                'create' => $request->user()?->can('create', AdvisoryRequest::class) ?? false,
            ],
            'statusOptions' => collect(AdvisoryRequestStatus::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(AdvisoryRequestType::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', AdvisoryRequest::class);

        return Inertia::render('Advisory/Create', [
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'categories' => AdvisoryCategory::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(AdvisoryRequestType::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
            'authDepartmentId' => $request->user()?->department_id,
        ]);
    }

    public function store(StoreAdvisoryRequestRequest $request, SubmitAdvisoryRequestAction $action): RedirectResponse
    {
        $advisoryRequest = $action->execute($request->validated(), $request->user());

        return to_route('advisory.show', $advisoryRequest)->with('success', __('Advisory request submitted successfully.'));
    }

    public function show(AdvisoryRequest $id): Response
    {
        $this->authorize('view', $id);

        $user = request()->user();

        $id->load([
            'department',
            'category',
            'requester',
            'directorReviewer',
            'assignedTeamLeader',
            'assignedLegalExpert',
            'assignments.assignedBy',
            'assignments.assignedTo',
            'responses.responder',
            'attachments.uploadedBy',
            'activities.causer',
        ]);

        $id->load([
            'comments' => function ($query) use ($user): void {
                $query->with('user');

                if ($user?->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER)) {
                    $query->where('is_internal', false);
                }
            },
        ]);

        return Inertia::render('Advisory/Show', [
            'requestItem' => AdvisoryRequestResource::make($id),
            'teamLeaders' => User::query()
                ->role(SystemRole::ADVISORY_TEAM_LEADER->value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'experts' => User::query()
                ->role(SystemRole::LEGAL_EXPERT->value)
                ->where('is_active', true)
                ->where('team_id', Team::query()->where('code', 'ADV')->value('id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'can' => [
                'review' => request()->user()?->can('review', $id) ?? false,
                'assign' => request()->user()?->can('assign', $id) ?? false,
                'respond' => request()->user()?->can('respond', $id) ?? false,
                'comment' => request()->user()?->can('comment', $id) ?? false,
                'attach' => request()->user()?->can('attach', $id) ?? false,
                'update' => request()->user()?->can('update', $id) ?? false,
                'requester_comment_public' => request()->user()?->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER) ?? false,
            ],
        ]);
    }

    public function edit(AdvisoryRequest $id): Response
    {
        $this->authorize('update', $id);

        $id->load(['department', 'category']);

        return Inertia::render('Advisory/Create', [
            'requestItem' => AdvisoryRequestResource::make($id),
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'categories' => AdvisoryCategory::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
            'typeOptions' => collect(AdvisoryRequestType::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
            'authDepartmentId' => request()->user()?->department_id,
            'mode' => 'edit',
        ]);
    }

    public function update(
        UpdateAdvisoryRequestRequest $request,
        AdvisoryRequest $id,
        UpdateReturnedAdvisoryRequestAction $action,
    ): RedirectResponse {
        $action->execute($id, $request->validated(), $request->user());

        return to_route('advisory.show', $id)->with('success', __('Advisory request resubmitted successfully.'));
    }

    public function directorReview(ReviewAdvisoryRequestRequest $request, AdvisoryRequest $id, DirectorReviewAdvisoryAction $action): RedirectResponse
    {
        $action->execute($id, $request->validated(), $request->user());

        return back()->with('success', __('Director review recorded.'));
    }

    public function assign(AssignAdvisoryRequestRequest $request, AdvisoryRequest $id, AssignAdvisoryToExpertAction $action): RedirectResponse
    {
        $action->execute($id, $request->validated(), $request->user());

        return back()->with('success', __('Advisory request assigned to expert.'));
    }

    public function respond(RecordAdvisoryResponseRequest $request, AdvisoryRequest $id, RecordAdvisoryResponseAction $action): RedirectResponse
    {
        $action->execute($id, $request->validated(), $request->user());

        return back()->with('success', __('Advisory response recorded.'));
    }

    public function addComment(StoreCommentRequest $request, AdvisoryRequest $advisoryRequest, AddCommentAction $action): RedirectResponse
    {
        $this->authorize('comment', $id);

        $action->execute(
            $id,
            $request->user(),
            $request->string('body')->toString(),
            (bool) $request->boolean('is_internal', ! $request->user()?->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER)),
        );

        return back()->with('success', __('Comment added.'));
    }

    public function addAttachment(StoreAttachmentRequest $request, AdvisoryRequest $id, StoreAttachmentAction $action): RedirectResponse
    {
        $this->authorize('create', Attachment::class);
        $this->authorize('attach', $id);

        $action->execute($id, $request->file('attachments'), $request->user());

        return back()->with('success', __('Attachment uploaded.'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ReviewLawsuitFilingRequestAction;
use App\Actions\StoreAttachmentAction;
use App\Actions\SubmitLawsuitFilingRequestAction;
use App\Enums\LawsuitRequestStatus;
use App\Http\Requests\LawsuitRequest\ReviewLawsuitFilingRequestRequest;
use App\Http\Requests\LawsuitRequest\StoreLawsuitFilingRequestRequest;
use App\Http\Requests\LawsuitRequest\UpdateLawsuitFilingRequestRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\LawsuitFilingRequestResource;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\LawsuitFilingRequest;
use App\Models\User;
use App\Notifications\LawsuitRequestReviewedNotification;
use App\Notifications\LawsuitRequestSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LawsuitFilingRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LawsuitFilingRequest::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(LawsuitRequestStatus::cases(), 'value'))],
        ]);

        $lawsuitRequests = LawsuitFilingRequest::query()
            ->with(['requestingDepartment', 'creator'])
            ->visibleTo($request->user())
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('request_code', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('date_submitted')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('LawsuitRequests/Index', [
            'filters' => $filters,
            'requests' => LawsuitFilingRequestResource::collection($lawsuitRequests),
            'can' => [
                'create' => $request->user()?->can('create', LawsuitFilingRequest::class) ?? false,
            ],
            'statusOptions' => collect(LawsuitRequestStatus::cases())->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LawsuitFilingRequest::class);

        return Inertia::render('LawsuitRequests/Create', [
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'authDepartmentId' => $request->user()?->department_id,
        ]);
    }

    public function store(StoreLawsuitFilingRequestRequest $request, SubmitLawsuitFilingRequestAction $action): RedirectResponse
    {
        $lawsuitRequest = $action->execute($request->validated(), $request->user());

        $this->notifyReviewers($lawsuitRequest, $request->user());

        return to_route('lawsuit-requests.show', $lawsuitRequest)
            ->with('success', __('Lawsuit filing request submitted successfully.'));
    }

    public function show(LawsuitFilingRequest $lawsuitFilingRequest): Response
    {
        $this->authorize('view', $lawsuitFilingRequest);

        $user = request()->user();

        $lawsuitFilingRequest->load([
            'requestingDepartment',
            'creator',
            'reviewer',
            'attachments.uploadedBy',
        ]);

        return Inertia::render('LawsuitRequests/Show', [
            'requestItem' => LawsuitFilingRequestResource::make($lawsuitFilingRequest)->resolve(),
            'can' => [
                'review' => $user?->can('review', $lawsuitFilingRequest) ?? false,
                'attach' => $user?->can('attach', $lawsuitFilingRequest) ?? false,
                'update' => $user?->can('update', $lawsuitFilingRequest) ?? false,
            ],
            'reviewStatusOptions' => collect([
                LawsuitRequestStatus::UNDER_REVIEW,
                LawsuitRequestStatus::APPROVED,
                LawsuitRequestStatus::REJECTED,
                LawsuitRequestStatus::RETURNED,
            ])->map(fn ($case) => [
                'label' => __("status.{$case->value}"),
                'value' => $case->value,
            ]),
        ]);
    }

    public function edit(LawsuitFilingRequest $lawsuitFilingRequest): Response
    {
        $this->authorize('update', $lawsuitFilingRequest);

        $lawsuitFilingRequest->load(['requestingDepartment']);

        return Inertia::render('LawsuitRequests/Create', [
            'requestItem' => LawsuitFilingRequestResource::make($lawsuitFilingRequest),
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'authDepartmentId' => request()->user()?->department_id,
            'mode' => 'edit',
        ]);
    }

    public function update(
        UpdateLawsuitFilingRequestRequest $request,
        LawsuitFilingRequest $lawsuitFilingRequest,
    ): RedirectResponse {
        $validated = $request->validated();

        $lawsuitFilingRequest->update([
            'requesting_department_id' => $validated['requesting_department_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => LawsuitRequestStatus::SUBMITTED,
        ]);

        if (! empty($validated['attachments'])) {
            app(StoreAttachmentAction::class)->execute(
                $lawsuitFilingRequest,
                $validated['attachments'],
                $request->user(),
            );
        }

        return to_route('lawsuit-requests.show', $lawsuitFilingRequest)
            ->with('success', __('Lawsuit filing request updated successfully.'));
    }

    public function destroy(LawsuitFilingRequest $lawsuitFilingRequest): RedirectResponse
    {
        $this->authorize('delete', $lawsuitFilingRequest);

        $lawsuitFilingRequest->delete();

        return to_route('lawsuit-requests.index')
            ->with('success', __('Lawsuit filing request deleted successfully.'));
    }

    public function review(
        ReviewLawsuitFilingRequestRequest $request,
        LawsuitFilingRequest $lawsuitFilingRequest,
        ReviewLawsuitFilingRequestAction $action,
    ): RedirectResponse {
        $updated = $action->execute($lawsuitFilingRequest, $request->validated(), $request->user());

        $creator = $updated->creator;
        if ($creator !== null) {
            $creator->notify(new LawsuitRequestReviewedNotification($updated, $request->user()));
        }

        return back()->with('success', __('Lawsuit filing request reviewed.'));
    }

    public function addAttachment(
        StoreAttachmentRequest $request,
        LawsuitFilingRequest $lawsuitFilingRequest,
        StoreAttachmentAction $action,
    ): RedirectResponse {
        $this->authorize('create', Attachment::class);
        $this->authorize('attach', $lawsuitFilingRequest);

        $action->execute($lawsuitFilingRequest, $request->file('attachments'), $request->user());

        return back()->with('success', __('Attachment uploaded.'));
    }

    private function notifyReviewers(LawsuitFilingRequest $lawsuitRequest, User $submittedBy): void
    {
        User::query()
            ->where('is_active', true)
            ->withAnyPermission(['lawsuit-requests.review'])
            ->each(function (User $reviewer) use ($lawsuitRequest, $submittedBy): void {
                $reviewer->notify(new LawsuitRequestSubmittedNotification($lawsuitRequest, $submittedBy));
            });
    }
}

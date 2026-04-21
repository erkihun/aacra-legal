<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ForwardComplaintToCommitteeAction;
use App\Actions\RecordComplaintCommitteeDecisionAction;
use App\Actions\RecordComplaintDepartmentResponseAction;
use App\Actions\StoreAttachmentAction;
use App\Actions\SubmitComplaintAction;
use App\Actions\UpdateComplaintAction;
use App\Actions\UpdateSystemSettingsGroupAction;
use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintCommitteeOutcome;
use App\Enums\ComplaintStatus;
use App\Enums\PriorityLevel;
use App\Enums\SystemSettingGroup;
use App\Http\Requests\Complaints\ComplaintFilterRequest;
use App\Http\Requests\Complaints\ForwardComplaintToCommitteeRequest;
use App\Http\Requests\Complaints\RecordComplaintDecisionRequest;
use App\Http\Requests\Complaints\RecordComplaintResponseRequest;
use App\Http\Requests\Complaints\StoreComplaintAttachmentRequest;
use App\Http\Requests\Complaints\StoreComplaintRequest;
use App\Http\Requests\Complaints\UpdateComplaintRequest;
use App\Http\Requests\Complaints\UpdateComplaintSettingsRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(ComplaintFilterRequest $request): Response
    {
        $this->authorize('viewAny', Complaint::class);

        $complaints = Complaint::query()
            ->with(['branch', 'department', 'complainant'])
            ->visibleTo($request->user())
            ->when($request->validated('search'), function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('complaint_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('complainant_name', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->validated('branch_id'), fn ($query, string $branchId) => $query->where('branch_id', $branchId))
            ->when($request->validated('department_id'), fn ($query, string $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->validated('complainant_type'), fn ($query, string $complainantType) => $query->where('complainant_type', $complainantType))
            ->when($request->validated('date_from'), fn ($query, string $dateFrom) => $query->whereDate('submitted_at', '>=', $dateFrom))
            ->when($request->validated('date_to'), fn ($query, string $dateTo) => $query->whereDate('submitted_at', '<=', $dateTo))
            ->latest('submitted_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Complaints/Index', [
            'filters' => $request->validated(),
            'complaints' => ComplaintResource::collection($complaints),
            'statusOptions' => collect(ComplaintStatus::cases())->map(fn (ComplaintStatus $status) => [
                'value' => $status->value,
                'label' => __("status.{$status->value}"),
            ])->values(),
            'complainantTypeOptions' => collect(ComplaintComplainantType::cases())->map(fn (ComplaintComplainantType $type) => [
                'value' => $type->value,
                'label' => __("complaints.complainant_types.{$type->value}"),
            ])->values(),
            'branches' => Branch::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'can' => [
                'create' => $request->user()?->can('create', Complaint::class) ?? false,
                'viewReports' => $request->user()?->can('complaints.reports.view') ?? false,
                'manageSettings' => $request->user()?->can('complaints.settings.manage') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Complaint::class);

        $derivedComplainantType = $this->derivedComplainantType($request->user());

        return Inertia::render('Complaints/Create', [
            'branches' => Branch::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn (PriorityLevel $priority) => [
                'value' => $priority->value,
                'label' => __("status.{$priority->value}"),
            ])->values(),
            'complainantTypeOptions' => collect(ComplaintComplainantType::cases())->map(fn (ComplaintComplainantType $type) => [
                'value' => $type->value,
                'label' => __("complaints.complainant_types.{$type->value}"),
            ])->values(),
            'derivedComplainantType' => $derivedComplainantType?->value,
            'authUser' => [
                'branch_id' => $request->user()?->branch_id,
                'department_id' => $request->user()?->department_id,
            ],
        ]);
    }

    public function edit(Complaint $complaint, Request $request): Response
    {
        $this->authorize('update', $complaint);

        return Inertia::render('Complaints/Create', [
            'mode' => 'edit',
            'complaintItem' => ComplaintResource::make($complaint->load(['branch', 'department', 'complainant']))->resolve(),
            'branches' => Branch::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'departments' => Department::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_am']),
            'priorityOptions' => collect(PriorityLevel::cases())->map(fn (PriorityLevel $priority) => [
                'value' => $priority->value,
                'label' => __("status.{$priority->value}"),
            ])->values(),
            'complainantTypeOptions' => collect(ComplaintComplainantType::cases())->map(fn (ComplaintComplainantType $type) => [
                'value' => $type->value,
                'label' => __("complaints.complainant_types.{$type->value}"),
            ])->values(),
            'derivedComplainantType' => $complaint->complainant_type?->value ?? $this->derivedComplainantType($request->user())?->value,
            'authUser' => [
                'branch_id' => $request->user()?->branch_id,
                'department_id' => $request->user()?->department_id,
            ],
        ]);
    }

    public function store(StoreComplaintRequest $request, SubmitComplaintAction $action): RedirectResponse
    {
        $complaint = $action->execute(
            $request->validated(),
            $request->user(),
            $request->file('attachments', []),
        );

        return to_route('complaints.show', $complaint)->with('success', __('Complaint submitted successfully.'));
    }

    public function update(Complaint $complaint, UpdateComplaintRequest $request, UpdateComplaintAction $action): RedirectResponse
    {
        $action->execute($complaint, $request->validated());

        return to_route('complaints.show', $complaint)->with('success', __('Complaint updated successfully.'));
    }

    public function show(Complaint $complaint): Response
    {
        $this->authorize('view', $complaint);

        $complaint->load([
            'complainant',
            'branch',
            'department',
            'assignedCommitteeUser',
            'attachments.uploadedBy',
            'responses.responder',
            'responses.responderDepartment',
            'responses.attachments.uploadedBy',
            'escalations.escalatedBy',
            'committeeDecisions.committeeActor',
            'committeeDecisions.attachments.uploadedBy',
            'histories.actor',
        ]);

        return Inertia::render('Complaints/Show', [
            'complaintItem' => ComplaintResource::make($complaint)->resolve(),
            'can' => [
                'update' => request()->user()?->can('update', $complaint) ?? false,
                'respondDepartment' => request()->user()?->can('respondDepartment', $complaint) ?? false,
                'forwardToCommittee' => request()->user()?->can('forwardToCommittee', $complaint) ?? false,
                'decideCommittee' => request()->user()?->can('decideCommittee', $complaint) ?? false,
                'attach' => request()->user()?->can('attach', $complaint) ?? false,
            ],
            'committeeOutcomeOptions' => collect(ComplaintCommitteeOutcome::cases())->map(fn (ComplaintCommitteeOutcome $outcome) => [
                'value' => $outcome->value,
                'label' => __("complaints.committee_outcomes.{$outcome->value}"),
            ])->values(),
        ]);
    }

    public function respondDepartment(
        RecordComplaintResponseRequest $request,
        Complaint $complaint,
        RecordComplaintDepartmentResponseAction $action,
    ): RedirectResponse {
        $action->execute(
            $complaint,
            $request->validated(),
            $request->user(),
            $request->file('attachments', []),
        );

        return back()->with('success', __('Complaint response recorded.'));
    }

    public function forwardToCommittee(
        ForwardComplaintToCommitteeRequest $request,
        Complaint $complaint,
        ForwardComplaintToCommitteeAction $action,
    ): RedirectResponse {
        $action->execute($complaint, $request->validated(), $request->user());

        return back()->with('success', __('Complaint forwarded to committee.'));
    }

    public function recordCommitteeDecision(
        RecordComplaintDecisionRequest $request,
        Complaint $complaint,
        RecordComplaintCommitteeDecisionAction $action,
    ): RedirectResponse {
        $action->execute(
            $complaint,
            $request->validated(),
            $request->user(),
            $request->file('attachments', []),
        );

        return back()->with('success', __('Committee decision recorded.'));
    }

    public function addAttachment(StoreComplaintAttachmentRequest $request, Complaint $complaint, StoreAttachmentAction $action): RedirectResponse
    {
        $this->authorize('create', Attachment::class);
        $this->authorize('attach', $complaint);

        $action->execute($complaint, $request->file('attachments'), $request->user());

        return back()->with('success', __('Attachment uploaded.'));
    }

    public function settings(): Response
    {
        abort_unless(request()->user()?->can('complaints.settings.manage') ?? false, 403);

        return Inertia::render('Complaints/Settings', [
            'settings' => $this->settings->group(SystemSettingGroup::COMPLAINTS),
            'committeeUsers' => User::query()
                ->where('is_active', true)
                ->withAnyPermission(['complaints.committee.review', 'complaints.committee.decide'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'supportedAttachmentTypes' => $this->settings->supportedUploadFileTypes(),
        ]);
    }

    public function updateSettings(
        UpdateComplaintSettingsRequest $request,
        UpdateSystemSettingsGroupAction $action,
    ): RedirectResponse {
        abort_unless($request->user()?->can('complaints.settings.manage') ?? false, 403);

        $action->execute(SystemSettingGroup::COMPLAINTS, $request->validated(), $request->user());

        return back()->with('success', __('Complaint settings updated.'));
    }

    private function derivedComplainantType(?User $user): ?ComplaintComplainantType
    {
        if ($user === null) {
            return null;
        }

        if ($user->hasRole('Complaint Client')) {
            return ComplaintComplainantType::CLIENT;
        }

        return $user->branch_id !== null
            ? ComplaintComplainantType::BRANCH_EMPLOYEE
            : ComplaintComplainantType::HEAD_OFFICE_EMPLOYEE;
    }
}

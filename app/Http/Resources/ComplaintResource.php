<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Complaint */
class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'complaint_number' => $this->complaint_number,
            'complainant_type' => $this->complainant_type?->value,
            'complainant_name' => $this->complainant_name,
            'complainant_email' => $this->complainant_email,
            'complainant_phone' => $this->complainant_phone,
            'subject' => $this->subject,
            'details' => $this->details,
            'category' => $this->category,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'is_overdue' => (bool) $this->is_overdue,
            'is_escalated' => (bool) $this->is_escalated,
            'is_auto_escalated' => (bool) $this->is_auto_escalated,
            'is_dissatisfied' => (bool) $this->is_dissatisfied,
            'dissatisfaction_reason' => $this->dissatisfaction_reason,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'department_response_deadline_at' => $this->department_response_deadline_at?->toIso8601String(),
            'department_responded_at' => $this->department_responded_at?->toIso8601String(),
            'forwarded_to_committee_at' => $this->forwarded_to_committee_at?->toIso8601String(),
            'committee_review_started_at' => $this->committee_review_started_at?->toIso8601String(),
            'committee_decision_at' => $this->committee_decision_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'complainant' => $this->whenLoaded('complainant', fn (): ?array => $this->complainant ? [
                'id' => $this->complainant->id,
                'name' => $this->complainant->name,
                'email' => $this->complainant->email,
            ] : null),
            'branch' => $this->whenLoaded('branch', fn (): ?array => $this->branch ? [
                'id' => $this->branch->id,
                'code' => $this->branch->code,
                'name_en' => $this->branch->name_en,
                'name_am' => $this->branch->name_am,
            ] : null),
            'department' => $this->whenLoaded('department', fn (): ?array => $this->department ? [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_en' => $this->department->name_en,
                'name_am' => $this->department->name_am,
            ] : null),
            'assigned_committee_user' => $this->whenLoaded('assignedCommitteeUser', fn (): ?array => $this->assignedCommitteeUser ? [
                'id' => $this->assignedCommitteeUser->id,
                'name' => $this->assignedCommitteeUser->name,
            ] : null),
            'attachments' => $this->relationLoaded('attachments')
                ? AttachmentResource::collection($this->attachments)->resolve($request)
                : [],
            'responses' => $this->relationLoaded('responses')
                ? ComplaintResponseResource::collection($this->responses)->resolve($request)
                : [],
            'committee_decisions' => $this->relationLoaded('committeeDecisions')
                ? ComplaintCommitteeDecisionResource::collection($this->committeeDecisions)->resolve($request)
                : [],
            'escalations' => $this->whenLoaded('escalations', fn () => $this->escalations->map(fn ($escalation) => [
                'id' => $escalation->id,
                'escalation_type' => $escalation->escalation_type?->value,
                'reason' => $escalation->reason,
                'escalated_at' => $escalation->escalated_at?->toIso8601String(),
                'escalated_by' => $escalation->relationLoaded('escalatedBy') && $escalation->escalatedBy ? [
                    'id' => $escalation->escalatedBy->id,
                    'name' => $escalation->escalatedBy->name,
                ] : null,
            ])->values()->all()),
            'histories' => $this->relationLoaded('histories')
                ? ComplaintStatusHistoryResource::collection($this->histories)->resolve($request)
                : [],
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'respond_department' => $request->user()?->can('respondDepartment', $this->resource) ?? false,
                'forward_to_committee' => $request->user()?->can('forwardToCommittee', $this->resource) ?? false,
                'review_committee' => $request->user()?->can('reviewCommittee', $this->resource) ?? false,
                'decide_committee' => $request->user()?->can('decideCommittee', $this->resource) ?? false,
            ],
        ];
    }
}

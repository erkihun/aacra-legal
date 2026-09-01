<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\RequestLetterTemplateData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvisoryRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'subject' => $this->subject,
            'description' => $this->description,
            'letter_template_id' => $this->letter_template_id,
            'letter_snapshot' => $this->letter_snapshot,
            'formal_letter' => app(RequestLetterTemplateData::class)->renderPayload(
                is_array($this->letter_snapshot) ? $this->letter_snapshot : null,
                $this->relationLoaded('letterTemplate') ? $this->letterTemplate : $this->letterTemplate()->first(),
                (string) ($this->description ?? ''),
                $this->request_number,
                $this->subject,
                $this->date_submitted?->toDateString(),
                $this->department ? [
                    'name_en' => $this->department?->name_en,
                    'name_am' => $this->department?->name_am,
                ] : null,
                $request->user()?->locale?->value ?? app()->getLocale(),
            ),
            'request_type' => $this->request_type?->value,
            'status' => $this->status?->value,
            'workflow_stage' => $this->workflow_stage?->value,
            'priority' => $this->priority?->value,
            'director_decision' => $this->director_decision?->value,
            'date_submitted' => $this->date_submitted?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'director_notes' => $this->director_notes,
            'internal_summary' => $this->internal_summary,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name_en' => $this->department?->name_en,
                'name_am' => $this->department?->name_am,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name_en' => $this->category?->name_en,
                'name_am' => $this->category?->name_am,
            ]),
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester?->id,
                'name' => $this->requester?->name,
            ]),
            'director_reviewer' => $this->whenLoaded('directorReviewer', fn () => [
                'id' => $this->directorReviewer?->id,
                'name' => $this->directorReviewer?->name,
            ]),
            'assigned_team_leader' => $this->whenLoaded('assignedTeamLeader', fn () => [
                'id' => $this->assignedTeamLeader?->id,
                'name' => $this->assignedTeamLeader?->name,
            ]),
            'assigned_legal_expert' => $this->whenLoaded('assignedLegalExpert', fn () => [
                'id' => $this->assignedLegalExpert?->id,
                'name' => $this->assignedLegalExpert?->name,
            ]),
            'assignments' => $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($assignment) => [
                'id' => $assignment->id,
                'assignment_role' => $assignment->assignment_role,
                'notes' => $assignment->notes,
                'assigned_at' => $assignment->assigned_at?->toIso8601String(),
                'assigned_by' => $assignment->assignedBy?->name,
                'assigned_to' => $assignment->assignedTo?->name,
            ])),
            'responses' => $this->whenLoaded('responses', fn () => $this->responses
                ->filter(fn ($response) => $request->user()?->can('view', $response) ?? false)
                ->map(fn ($response) => [
                    'id' => $response->id,
                    'subject' => $response->subject ?? $response->summary,
                    'response' => $response->response ?? $response->advice_text ?? $response->summary,
                    'responded_at' => $response->responded_at?->toIso8601String(),
                    'approval_status' => $response->approval_status ?? 'pending',
                    'approved_at' => $response->approved_at?->toIso8601String(),
                    'approver' => $response->approver?->name,
                    'responder' => $response->responder?->name,
                    'can_update' => $request->user()?->can('update', $response) ?? false,
                    'can_delete' => $request->user()?->can('delete', $response) ?? false,
                    'can_approve' => $request->user()?->can('approve', $response) ?? false,
                    'attachments' => $response->relationLoaded('attachments')
                        ? AttachmentResource::collection($response->attachments)->resolve($request)
                        : [],
                ])
                ->values()
                ->all()),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}

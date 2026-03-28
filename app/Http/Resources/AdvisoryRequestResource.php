<?php

declare(strict_types=1);

namespace App\Http\Resources;

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
            'responses' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($response) => [
                'id' => $response->id,
                'response_type' => $response->response_type?->value,
                'summary' => $response->summary,
                'advice_text' => $response->advice_text,
                'follow_up_notes' => $response->follow_up_notes,
                'responded_at' => $response->responded_at?->toIso8601String(),
                'responder' => $response->responder?->name,
            ])),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
        ];
    }
}

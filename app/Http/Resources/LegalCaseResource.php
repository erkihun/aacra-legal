<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'case_number' => $this->case_number,
            'external_court_file_number' => $this->external_court_file_number,
            'main_case_type' => $this->main_case_type?->value,
            'plaintiff' => $this->plaintiff,
            'defendant' => $this->defendant,
            'bench_or_chamber' => $this->bench_or_chamber,
            'claim_summary' => $this->claim_summary,
            'institution_position' => $this->institution_position,
            'amount' => $this->amount,
            'crime_scene' => $this->crime_scene,
            'police_station' => $this->police_station,
            'stolen_property_type' => $this->stolen_property_type,
            'stolen_property_estimated_value' => $this->stolen_property_estimated_value,
            'suspect_names' => $this->suspect_names,
            'statement_date' => $this->statement_date?->toDateString(),
            'status' => $this->status?->value,
            'workflow_stage' => $this->workflow_stage?->value,
            'priority' => $this->priority?->value,
            'director_decision' => $this->director_decision?->value,
            'outcome' => $this->outcome,
            'filing_date' => $this->filing_date?->toDateString(),
            'next_hearing_date' => $this->next_hearing_date?->toDateString(),
            'decision_date' => $this->decision_date?->toDateString(),
            'appeal_deadline' => $this->appeal_deadline?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'court' => $this->whenLoaded('court', fn () => [
                'id' => $this->court?->id,
                'name_en' => $this->court?->name_en,
                'name_am' => $this->court?->name_am,
            ]),
            'case_type' => $this->whenLoaded('caseType', fn () => [
                'id' => $this->caseType?->id,
                'name_en' => $this->caseType?->name_en,
                'name_am' => $this->caseType?->name_am,
            ]),
            'registered_by' => $this->whenLoaded('registeredBy', fn () => [
                'id' => $this->registeredBy?->id,
                'name' => $this->registeredBy?->name,
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
            'hearings' => $this->whenLoaded('hearings', fn () => $this->hearings->map(fn ($hearing) => [
                'id' => $hearing->id,
                'hearing_date' => $hearing->hearing_date?->toDateString(),
                'next_hearing_date' => $hearing->next_hearing_date?->toDateString(),
                'appearance_status' => $hearing->appearance_status,
                'summary' => $hearing->summary,
                'institution_position' => $hearing->institution_position,
                'court_decision' => $hearing->court_decision,
                'outcome' => $hearing->outcome,
                'recorded_by' => $hearing->recordedBy?->name,
                'can_update' => $request->user()?->can('update', $hearing) ?? false,
                'can_delete' => $request->user()?->can('delete', $hearing) ?? false,
            ])),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments
                ->map(fn ($comment) => CommentResource::make($comment)->resolve($request))
                ->values()
                ->all()),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments
                ->map(fn ($attachment) => AttachmentResource::make($attachment)->resolve($request))
                ->values()
                ->all()),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}

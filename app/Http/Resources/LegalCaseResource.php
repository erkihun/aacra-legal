<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\LegalCaseMainType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $overview = $this->buildOverviewFields();

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
            'reopened_at' => $this->reopened_at?->toIso8601String(),
            'reopen_reason' => $this->reopen_reason,
            'overview_fields' => $overview['fields'],
            'overview_description_label' => $overview['description_label'],
            'overview_description_html' => $overview['description_html'],
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
            'reopened_by' => $this->whenLoaded('reopenedBy', fn () => [
                'id' => $this->reopenedBy?->id,
                'name' => $this->reopenedBy?->name,
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

    /**
     * @return array{fields: array<int, array{key: string, label: string, value: string}>, description_label: string, description_html: string|null}
     */
    private function buildOverviewFields(): array
    {
        $fields = [
            $this->alwaysField('case_number', __('cases.case_number'), $this->case_number),
            $this->alwaysField(
                'main_case_type',
                __('cases.main_case_type_label'),
                $this->main_case_type ? __("cases.main_case_type.{$this->main_case_type->value}") : null,
            ),
            $this->optionalField('status', __('common.status'), $this->status ? __("status.{$this->status->value}") : null),
            $this->optionalField('registrar', __('cases.registrar'), $this->registeredBy?->name),
            $this->optionalField('court_file_number', __('cases.court_file_number'), $this->external_court_file_number),
            $this->optionalField('team_leader', __('cases.team_leader'), $this->assignedTeamLeader?->name ?? __('common.unassigned')),
            $this->optionalField('expert', __('cases.expert'), $this->assignedLegalExpert?->name ?? __('common.unassigned')),
            $this->optionalField('next_hearing', __('cases.next_hearing'), $this->next_hearing_date?->toDateString()),
        ];

        $fields = [
            ...$fields,
            ...match ($this->main_case_type) {
                LegalCaseMainType::CRIME => $this->crimeOverviewFields(),
                LegalCaseMainType::LABOUR_DISPUTE => $this->labourOverviewFields(),
                LegalCaseMainType::CIVIL_LAW => $this->civilOverviewFields(),
                default => $this->fallbackOverviewFields(),
            },
        ];

        return [
            'fields' => array_values(array_filter($fields)),
            'description_label' => $this->main_case_type === LegalCaseMainType::CRIME
                ? __('cases.crime_details')
                : __('cases.detailed_description'),
            'description_html' => filled($this->claim_summary) ? $this->claim_summary : null,
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    private function civilOverviewFields(): array
    {
        return array_values(array_filter([
            $this->optionalField('court', __('cases.court'), $this->localizedRelatedName($this->court)),
            $this->optionalField('civil_law_type', __('cases.civil_law_type'), $this->localizedRelatedName($this->caseType)),
            $this->optionalField('plaintiff', __('cases.plaintiff'), $this->plaintiff),
            $this->optionalField('defendant', __('cases.defendant'), $this->defendant),
            $this->optionalField('amount', __('cases.amount'), $this->formattedDecimal($this->amount)),
        ]));
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    private function labourOverviewFields(): array
    {
        return array_values(array_filter([
            $this->optionalField('court', __('cases.court'), $this->localizedRelatedName($this->court)),
            $this->optionalField('plaintiff', __('cases.plaintiff'), $this->plaintiff),
            $this->optionalField('defendant', __('cases.defendant'), $this->defendant),
            $this->optionalField('amount', __('cases.amount'), $this->formattedDecimal($this->amount)),
        ]));
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    private function crimeOverviewFields(): array
    {
        return array_values(array_filter([
            $this->optionalField('court', __('cases.court'), $this->localizedRelatedName($this->court)),
            $this->optionalField('crime_scene', __('cases.crime_scene'), $this->crime_scene),
            $this->optionalField('police_station', __('cases.police_station'), $this->police_station),
            $this->optionalField('stolen_property_type', __('cases.stolen_property_type'), $this->stolen_property_type),
            $this->optionalField('stolen_property_estimated_value', __('cases.stolen_property_estimated_value'), $this->formattedDecimal($this->stolen_property_estimated_value)),
            $this->optionalField('suspect_names', __('cases.suspect_names'), $this->suspect_names),
            $this->optionalField('statement_date', __('cases.statement_date'), $this->statement_date?->toDateString()),
        ]));
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    private function fallbackOverviewFields(): array
    {
        return array_values(array_filter([
            $this->optionalField('court', __('cases.court'), $this->localizedRelatedName($this->court)),
            $this->optionalField('case_type', __('cases.case_type'), $this->localizedRelatedName($this->caseType)),
            $this->optionalField('plaintiff', __('cases.plaintiff'), $this->plaintiff),
            $this->optionalField('defendant', __('cases.defendant'), $this->defendant),
            $this->optionalField('amount', __('cases.amount'), $this->formattedDecimal($this->amount)),
            $this->optionalField('crime_scene', __('cases.crime_scene'), $this->crime_scene),
            $this->optionalField('police_station', __('cases.police_station'), $this->police_station),
            $this->optionalField('stolen_property_type', __('cases.stolen_property_type'), $this->stolen_property_type),
            $this->optionalField('stolen_property_estimated_value', __('cases.stolen_property_estimated_value'), $this->formattedDecimal($this->stolen_property_estimated_value)),
            $this->optionalField('suspect_names', __('cases.suspect_names'), $this->suspect_names),
            $this->optionalField('statement_date', __('cases.statement_date'), $this->statement_date?->toDateString()),
        ]));
    }

    /**
     * @return array{key: string, label: string, value: string}|null
     */
    private function alwaysField(string $key, string $label, ?string $value): ?array
    {
        if (! filled($value)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
        ];
    }

    /**
     * @return array{key: string, label: string, value: string}|null
     */
    private function optionalField(string $key, string $label, ?string $value): ?array
    {
        if (! filled($value)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
        ];
    }

    private function localizedRelatedName(?object $related): ?string
    {
        if ($related === null) {
            return null;
        }

        $locale = app()->getLocale();
        $amharicName = $related->name_am ?? null;
        $englishName = $related->name_en ?? null;

        return $locale === 'am'
            ? ($amharicName ?: $englishName)
            : ($englishName ?: $amharicName);
    }

    private function formattedDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? number_format((float) $value, 2, '.', ',') : (string) $value;
    }
}

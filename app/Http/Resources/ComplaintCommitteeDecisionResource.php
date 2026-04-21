<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ComplaintCommitteeDecision */
class ComplaintCommitteeDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investigation_notes' => $this->investigation_notes,
            'decision_summary' => $this->decision_summary,
            'decision_detail' => $this->decision_detail,
            'decision_date' => $this->decision_date?->toIso8601String(),
            'outcome' => $this->outcome?->value,
            'committee_actor' => $this->whenLoaded('committeeActor', fn (): ?array => $this->committeeActor ? [
                'id' => $this->committeeActor->id,
                'name' => $this->committeeActor->name,
            ] : null),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments'))->resolve($request),
        ];
    }
}

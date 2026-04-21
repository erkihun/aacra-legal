<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ComplaintStatusHistory */
class ComplaintStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status?->value,
            'action' => $this->action,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'acted_at' => $this->acted_at?->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn (): ?array => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ] : null),
        ];
    }
}

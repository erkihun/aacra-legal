<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ComplaintResponse */
class ComplaintResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'response_content' => $this->response_content,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'responder' => $this->whenLoaded('responder', fn (): ?array => $this->responder ? [
                'id' => $this->responder->id,
                'name' => $this->responder->name,
            ] : null),
            'responder_department' => $this->whenLoaded('responderDepartment', fn (): ?array => $this->responderDepartment ? [
                'id' => $this->responderDepartment->id,
                'name_en' => $this->responderDepartment->name_en,
                'name_am' => $this->responderDepartment->name_am,
            ] : null),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments'))->resolve($request),
        ];
    }
}

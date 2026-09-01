<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\RequestLetterTemplateData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LawsuitFilingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'subject' => $this->subject,
            'description' => $this->description,
            'letter_template_id' => $this->letter_template_id,
            'letter_snapshot' => $this->letter_snapshot,
            'formal_letter' => app(RequestLetterTemplateData::class)->renderPayload(
                is_array($this->letter_snapshot) ? $this->letter_snapshot : null,
                $this->relationLoaded('letterTemplate') ? $this->letterTemplate : $this->letterTemplate()->first(),
                (string) ($this->description ?? ''),
                $this->request_code,
                $this->subject,
                $this->date_submitted?->toDateString(),
                $this->requestingDepartment ? [
                    'name_en' => $this->requestingDepartment?->name_en,
                    'name_am' => $this->requestingDepartment?->name_am,
                ] : null,
                $request->user()?->locale?->value ?? app()->getLocale(),
            ),
            'status' => $this->status?->value,
            'reviewer_notes' => $this->reviewer_notes,
            'date_submitted' => $this->date_submitted?->toDateString(),
            'requesting_department' => $this->whenLoaded('requestingDepartment', fn () => [
                'id' => $this->requestingDepartment?->id,
                'name_en' => $this->requestingDepartment?->name_en,
                'name_am' => $this->requestingDepartment?->name_am,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
            ]),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'can_update' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
        ];
    }
}

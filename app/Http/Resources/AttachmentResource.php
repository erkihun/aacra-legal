<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'view_url' => route('attachments.view', $this->resource),
            'download_url' => route('attachments.download', $this->resource),
            'delete_url' => route('attachments.destroy', $this->resource),
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'uploaded_by' => $this->uploadedBy?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

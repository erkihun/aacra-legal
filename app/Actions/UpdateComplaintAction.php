<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Complaint;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Str;

class UpdateComplaintAction
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
        private readonly StoreAttachmentAction $storeAttachmentAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(Complaint $complaint, array $attributes, array $attachments = []): Complaint
    {
        $complaintEssence = trim((string) ($attributes['complaint_essence'] ?? ''));
        $subject = Str::limit(Str::of($complaintEssence)->replaceMatches('/\s+/', ' ')->trim()->value(), 255, '');

        $complaint->update([
            'branch_id' => $attributes['branch_id'] ?? null,
            'department_id' => $attributes['department_id'],
            'concerned_employee_name' => $this->nullableString($attributes['concerned_employee_name'] ?? null),
            'complainant_name' => trim((string) $attributes['complainant_name']),
            'complainant_phone' => trim((string) $attributes['complainant_phone']),
            'complainant_city' => $this->nullableString($attributes['complainant_city'] ?? null),
            'complainant_sub_city' => $this->nullableString($attributes['complainant_sub_city'] ?? null),
            'complainant_woreda' => $this->nullableString($attributes['complainant_woreda'] ?? null),
            'complainant_house_number' => $this->nullableString($attributes['complainant_house_number'] ?? null),
            'subject' => $subject,
            'complaint_essence' => $complaintEssence,
            'details' => $this->richTextSanitizer->sanitize($complaintEssence),
            'category' => $attributes['category'] ?? $complaint->category,
            'evidence_note' => $this->nullableString($attributes['evidence_note'] ?? null),
            'requested_resolution' => trim((string) $attributes['requested_resolution']),
            'priority' => $attributes['priority'] ?? ($complaint->priority?->value ?? null),
            'incident_date' => $attributes['incident_date'],
            'incident_sub_city' => $this->nullableString($attributes['incident_sub_city'] ?? null),
            'incident_woreda' => $this->nullableString($attributes['incident_woreda'] ?? null),
        ]);

        if ($attachments !== []) {
            $this->storeAttachmentAction->execute($complaint, $attachments, $complaint->complainant);
        }

        return $complaint->fresh(['complainant', 'branch', 'department']);
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}

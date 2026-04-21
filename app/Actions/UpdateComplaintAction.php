<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Complaint;
use App\Support\RichTextSanitizer;

class UpdateComplaintAction
{
    public function __construct(
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Complaint $complaint, array $attributes): Complaint
    {
        $complaint->update([
            'branch_id' => $attributes['branch_id'] ?? null,
            'department_id' => $attributes['department_id'],
            'subject' => trim((string) $attributes['subject']),
            'details' => $this->richTextSanitizer->sanitize((string) $attributes['details']),
            'category' => $attributes['category'] ?? null,
            'priority' => $attributes['priority'] ?? null,
        ]);

        return $complaint->fresh(['complainant', 'branch', 'department']);
    }
}

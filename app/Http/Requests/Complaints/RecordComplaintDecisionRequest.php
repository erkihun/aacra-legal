<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Enums\ComplaintCommitteeOutcome;
use App\Models\Complaint;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordComplaintDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        return $complaint !== null && ($this->user()?->can('decideCommittee', $complaint) ?? false);
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $maxSize = $settings->complaintMaxAttachmentSizeMb() * 1024;
        $extensions = implode(',', $settings->complaintAllowedAttachmentTypes());

        return [
            'investigation_notes' => ['nullable', 'string'],
            'decision_summary' => ['required', 'string', 'max:255'],
            'decision_detail' => ['required', 'string', 'min:10'],
            'outcome' => ['required', Rule::in(array_column(ComplaintCommitteeOutcome::cases(), 'value'))],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', "mimes:{$extensions}", "max:{$maxSize}"],
        ];
    }
}

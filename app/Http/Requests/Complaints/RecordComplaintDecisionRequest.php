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

    public function attributes(): array
    {
        return [
            'investigation_notes' => __('complaints.validation_attributes.investigation_notes'),
            'decision_summary' => __('complaints.validation_attributes.decision_summary'),
            'decision_detail' => __('complaints.validation_attributes.decision_detail'),
            'outcome' => __('complaints.validation_attributes.outcome'),
            'attachments' => __('complaints.validation_attributes.attachments'),
            'attachments.*' => __('complaints.validation_attributes.attachment_file'),
        ];
    }
}

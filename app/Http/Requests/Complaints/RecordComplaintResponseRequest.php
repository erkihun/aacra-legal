<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Models\Complaint;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class RecordComplaintResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        return $complaint !== null && ($this->user()?->can('respondDepartment', $complaint) ?? false);
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $maxSize = $settings->complaintMaxAttachmentSizeMb() * 1024;
        $extensions = implode(',', $settings->complaintAllowedAttachmentTypes());

        return [
            'subject' => ['required', 'string', 'max:255'],
            'response_content' => ['required', 'string', 'min:10'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', "mimes:{$extensions}", "max:{$maxSize}"],
        ];
    }
}

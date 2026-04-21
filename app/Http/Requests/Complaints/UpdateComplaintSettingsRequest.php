<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('complaints.settings.manage') ?? false;
    }

    public function rules(): array
    {
        $supportedTypes = app(SystemSettingsService::class)->supportedUploadFileTypes();

        return [
            'default_response_deadline_days' => ['required', 'integer', 'min:1', 'max:90'],
            'auto_escalation_enabled' => ['required', 'boolean'],
            'reminder_interval_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'committee_notification_user_ids' => ['nullable', 'array'],
            'committee_notification_user_ids.*' => ['uuid', 'exists:users,id'],
            'allow_client_self_registration' => ['required', 'boolean'],
            'complaint_code_prefix' => ['required', 'string', 'max:10'],
            'allowed_attachment_types' => ['required', 'array', 'min:1'],
            'allowed_attachment_types.*' => ['string', 'in:'.implode(',', $supportedTypes)],
            'max_attachment_size_mb' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}

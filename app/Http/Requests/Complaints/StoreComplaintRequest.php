<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Enums\PriorityLevel;
use App\Models\Complaint;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Complaint::class) ?? false;
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $maxSize = $settings->complaintMaxAttachmentSizeMb() * 1024;
        $extensions = implode(',', $settings->complaintAllowedAttachmentTypes());

        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'subject' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'min:10'],
            'category' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', Rule::in(array_column(PriorityLevel::cases(), 'value'))],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', "mimes:{$extensions}", "max:{$maxSize}"],
        ];
    }
}

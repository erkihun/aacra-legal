<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Models\Attachment;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Attachment::class) ?? false;
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $maxSize = $settings->complaintMaxAttachmentSizeMb() * 1024;
        $extensions = implode(',', $settings->complaintAllowedAttachmentTypes());

        return [
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', "mimes:{$extensions}", "max:{$maxSize}"],
        ];
    }
}

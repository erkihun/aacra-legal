<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $allowedFileTypes = $settings->allowedUploadFileTypes();
        $allowedMimeTypes = $settings->allowedUploadMimeTypes();
        $maxUploadSizeKb = $settings->maxUploadSizeMb() * 1024;

        return [
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:'.implode(',', $allowedFileTypes),
                'extensions:'.implode(',', $allowedFileTypes),
                'mimetypes:'.implode(',', $allowedMimeTypes),
                "max:{$maxUploadSizeKb}",
            ],
        ];
    }
}

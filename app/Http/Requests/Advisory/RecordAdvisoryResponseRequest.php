<?php

declare(strict_types=1);

namespace App\Http\Requests\Advisory;

use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class RecordAdvisoryResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('advisoryRequest');

        return $this->user()?->can('respond', $request) ?? false;
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $allowedFileTypes = $settings->allowedUploadFileTypes();
        $allowedMimeTypes = $settings->allowedUploadMimeTypes();
        $maxUploadSizeKb = $settings->maxUploadSizeMb() * 1024;

        return [
            'subject' => ['required', 'string', 'max:255'],
            'response' => ['required', 'string', 'min:10'],
            'attachments' => ['nullable', 'array', 'min:1', 'max:5'],
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

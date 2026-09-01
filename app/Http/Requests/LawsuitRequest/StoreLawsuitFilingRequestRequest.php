<?php

declare(strict_types=1);

namespace App\Http\Requests\LawsuitRequest;

use App\Models\LawsuitFilingRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreLawsuitFilingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LawsuitFilingRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'requesting_department_id' => ['required', 'uuid', 'exists:departments,id'],
            'subject' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }
}

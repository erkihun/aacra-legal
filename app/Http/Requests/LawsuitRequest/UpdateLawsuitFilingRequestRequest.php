<?php

declare(strict_types=1);

namespace App\Http\Requests\LawsuitRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLawsuitFilingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lawsuitRequest = $this->route('lawsuitFilingRequest');

        return $this->user()?->can('update', $lawsuitRequest) ?? false;
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

<?php

declare(strict_types=1);

namespace App\Http\Requests\Requester;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLawsuitRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('requester')->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }
}

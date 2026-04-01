<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('attachment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'original_name' => ['required', 'string', 'max:255'],
        ];
    }
}

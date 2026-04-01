<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }
}

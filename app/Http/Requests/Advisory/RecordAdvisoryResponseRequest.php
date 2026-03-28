<?php

declare(strict_types=1);

namespace App\Http\Requests\Advisory;

use App\Enums\AdvisoryRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordAdvisoryResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('advisoryRequest');

        return $this->user()?->can('respond', $request) ?? false;
    }

    public function rules(): array
    {
        return [
            'response_type' => ['required', Rule::enum(AdvisoryRequestType::class)],
            'summary' => ['required', 'string', 'min:10'],
            'advice_text' => [
                Rule::requiredIf($this->input('response_type') === AdvisoryRequestType::WRITTEN->value),
                'nullable',
                'string',
            ],
            'follow_up_notes' => ['nullable', 'string'],
        ];
    }
}

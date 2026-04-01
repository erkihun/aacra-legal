<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseHearingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('hearing')) ?? false;
    }

    public function rules(): array
    {
        return [
            'hearing_date' => ['required', 'date'],
            'next_hearing_date' => ['nullable', 'date', 'after_or_equal:hearing_date'],
            'appearance_status' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string', 'min:10'],
            'court_decision' => ['nullable', 'string'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;

class CloseLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $legalCase = $this->route('legalCase');

        return $this->user()?->can('close', $legalCase) ?? false;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string'],
            'decision_date' => ['nullable', 'date'],
            'appeal_deadline' => ['nullable', 'date', 'after_or_equal:decision_date'],
        ];
    }
}

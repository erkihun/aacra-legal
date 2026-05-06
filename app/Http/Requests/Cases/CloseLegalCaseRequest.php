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
            'closing_date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'closing_date' => __('cases.closing_date'),
        ];
    }
}

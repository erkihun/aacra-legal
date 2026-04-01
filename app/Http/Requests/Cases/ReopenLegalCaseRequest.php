<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use Illuminate\Foundation\Http\FormRequest;

class ReopenLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $legalCase = $this->route('legalCase');

        return $this->user()?->can('reopen', $legalCase) ?? false;
    }

    public function rules(): array
    {
        return [
            'reopen_reason' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}

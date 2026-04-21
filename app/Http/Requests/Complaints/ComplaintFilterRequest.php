<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(ComplaintStatus::cases(), 'value'))],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'complainant_type' => ['nullable', Rule::in(array_column(ComplaintComplainantType::cases(), 'value'))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}

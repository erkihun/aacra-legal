<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Enums\PriorityLevel;
use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        return $complaint !== null && ($this->user()?->can('update', $complaint) ?? false);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'subject' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'min:10'],
            'category' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', Rule::in(array_column(PriorityLevel::cases(), 'value'))],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;

class ForwardComplaintToCommitteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Complaint|null $complaint */
        $complaint = $this->route('complaint');

        return $complaint !== null && ($this->user()?->can('forwardToCommittee', $complaint) ?? false);
    }

    public function rules(): array
    {
        return [
            'dissatisfaction_reason' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}

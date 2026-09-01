<?php

declare(strict_types=1);

namespace App\Http\Requests\LawsuitRequest;

use App\Enums\LawsuitRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewLawsuitFilingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lawsuitRequest = $this->route('lawsuitFilingRequest');

        return $this->user()?->can('review', $lawsuitRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    LawsuitRequestStatus::APPROVED->value,
                    LawsuitRequestStatus::REJECTED->value,
                    LawsuitRequestStatus::RETURNED->value,
                    LawsuitRequestStatus::UNDER_REVIEW->value,
                ]),
            ],
            'reviewer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

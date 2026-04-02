<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\Enums\TeamType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $legalCase = $this->route('legalCase');

        return $this->user()?->can('assign', $legalCase) ?? false;
    }

    public function rules(): array
    {
        return [
            'assigned_legal_expert_id' => [
                'required',
                'uuid',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $expert = User::query()->with('team')->find($value);

                    if (
                        $expert === null
                        || ! $expert->is_active
                        || ! $expert->canHandleAssignedCases()
                        || $expert->team?->type !== TeamType::LITIGATION
                    ) {
                        $fail(__('The selected litigation expert is invalid.'));
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

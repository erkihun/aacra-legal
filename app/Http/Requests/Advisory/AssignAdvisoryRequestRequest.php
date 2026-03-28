<?php

declare(strict_types=1);

namespace App\Http\Requests\Advisory;

use App\Enums\SystemRole;
use App\Enums\TeamType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignAdvisoryRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('advisoryRequest');

        return $this->user()?->can('assign', $request) ?? false;
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
                        || ! $expert->hasSystemRole(SystemRole::LEGAL_EXPERT)
                        || $expert->team?->type !== TeamType::ADVISORY
                    ) {
                        $fail(__('The selected advisory expert is invalid.'));
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

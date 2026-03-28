<?php

declare(strict_types=1);

namespace App\Http\Requests\Advisory;

use App\Enums\DirectorDecision;
use App\Enums\SystemRole;
use App\Enums\TeamType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewAdvisoryRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('advisoryRequest');

        return $this->user()?->can('review', $request) ?? false;
    }

    public function rules(): array
    {
        return [
            'director_decision' => ['required', Rule::enum(DirectorDecision::class)],
            'director_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_team_leader_id' => [
                Rule::requiredIf($this->input('director_decision') === DirectorDecision::APPROVED->value),
                'nullable',
                'uuid',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $teamLeader = User::query()->with('team')->find($value);

                    if (
                        $teamLeader === null
                        || ! $teamLeader->is_active
                        || ! $teamLeader->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER)
                        || $teamLeader->team?->type !== TeamType::ADVISORY
                    ) {
                        $fail(__('The selected advisory team leader is invalid.'));
                    }
                },
            ],
        ];
    }
}

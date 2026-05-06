<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TeamType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            ? ($this->user()?->can('update', $team) ?? false)
            : ($this->user()?->can('create', Team::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => str($this->input('code'))->upper()->trim()->toString(),
            'name_en' => trim((string) $this->input('name_en')),
            'name_am' => trim((string) $this->input('name_am')),
            'supports_advisory' => $this->resolveSupportFlag('supports_advisory', TeamType::ADVISORY),
            'supports_court_case' => $this->resolveSupportFlag('supports_court_case', TeamType::LITIGATION),
        ]);
    }

    public function rules(): array
    {
        /** @var Team|null $team */
        $team = $this->route('team');

        return [
            'leader_user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at')->where('is_active', true)),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Team::class, 'code')
                    ->ignore($team?->getKey())
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_column(TeamType::cases(), 'value'))],
            'supports_advisory' => ['required', 'boolean'],
            'supports_court_case' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $supportsAdvisory = (bool) $this->validated('supports_advisory');
                $supportsCourtCase = (bool) $this->validated('supports_court_case');

                if ($supportsAdvisory || $supportsCourtCase || $this->allowsLegacyNonWorkflowTeam()) {
                    return;
                }

                $validator->errors()->add('supports_advisory', __('teams.validation.capability_required'));
            },
        ];
    }

    private function allowsLegacyNonWorkflowTeam(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team
            && $team->type === TeamType::ADMINISTRATION
            && ! $team->supportsAdvisory()
            && ! $team->supportsCourtCase();
    }

    private function resolveSupportFlag(string $key, TeamType $legacyType): bool
    {
        if ($this->has($key)) {
            return $this->boolean($key);
        }

        return $this->input('type') === $legacyType->value;
    }
}

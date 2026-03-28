<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TeamType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
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
            'type' => ['required', Rule::in(array_column(TeamType::cases(), 'value'))],
            'is_active' => ['required', 'boolean'],
        ];
    }
}

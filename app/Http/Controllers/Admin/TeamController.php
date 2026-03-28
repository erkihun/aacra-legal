<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PersistTeamAction;
use App\Enums\TeamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Team::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $teams = Team::query()
            ->with('leader')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_am', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy('name_en')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Team $team): array => [
                'id' => $team->id,
                'code' => $team->code,
                'name_en' => $team->name_en,
                'name_am' => $team->name_am,
                'type' => $team->type?->value,
                'leader' => $team->leader ? [
                    'id' => $team->leader->id,
                    'name' => $team->leader->name,
                ] : null,
                'is_active' => $team->is_active,
            ]);

        return Inertia::render('Admin/Teams/Index', [
            'filters' => $filters,
            'teams' => $teams,
            'typeOptions' => collect(TeamType::cases())->map(fn (TeamType $type): array => [
                'value' => $type->value,
                'label' => __("team_type.{$type->value}"),
            ])->values(),
            'can' => [
                'create' => $request->user()?->can('create', Team::class) ?? false,
                'update' => $request->user()?->can('teams.manage') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Team::class);

        return Inertia::render('Admin/Teams/Form', [
            'teamItem' => null,
            'leaderOptions' => $this->leaderOptions(),
            'typeOptions' => collect(TeamType::cases())->map(fn (TeamType $type): array => [
                'value' => $type->value,
                'label' => __("team_type.{$type->value}"),
            ])->values(),
            'canDelete' => false,
        ]);
    }

    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load(['leader'])->loadCount(['users']);

        return Inertia::render('Admin/Teams/Show', [
            'teamItem' => [
                'id' => $team->id,
                'leader_user_id' => $team->leader_user_id,
                'code' => $team->code,
                'name_en' => $team->name_en,
                'name_am' => $team->name_am,
                'type' => $team->type?->value,
                'is_active' => $team->is_active,
                'leader' => $team->leader ? [
                    'id' => $team->leader->id,
                    'name' => $team->leader->name,
                ] : null,
                'stats' => [
                    'users' => $team->users_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $team) ?? false,
                'delete' => request()->user()?->can('delete', $team) ?? false,
            ],
        ]);
    }

    public function store(TeamRequest $request, PersistTeamAction $action): RedirectResponse
    {
        $team = $action->execute(null, $request->validated());

        return to_route('teams.edit', $team)->with('success', __('Team created successfully.'));
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('Admin/Teams/Form', [
            'teamItem' => [
                'id' => $team->id,
                'leader_user_id' => $team->leader_user_id,
                'code' => $team->code,
                'name_en' => $team->name_en,
                'name_am' => $team->name_am,
                'type' => $team->type?->value,
                'is_active' => $team->is_active,
            ],
            'leaderOptions' => $this->leaderOptions(),
            'typeOptions' => collect(TeamType::cases())->map(fn (TeamType $type): array => [
                'value' => $type->value,
                'label' => __("team_type.{$type->value}"),
            ])->values(),
            'canDelete' => request()->user()?->can('delete', $team) ?? false,
        ]);
    }

    public function update(TeamRequest $request, Team $team, PersistTeamAction $action): RedirectResponse
    {
        $action->execute($team, $request->validated());

        return to_route('teams.edit', $team)->with('success', __('Team updated successfully.'));
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        if ($team->users()->exists()) {
            return back()->with('error', __('This team cannot be deleted while it is still assigned to users.'));
        }

        $team->delete();

        return to_route('teams.index')->with('success', __('Team deleted successfully.'));
    }

    private function leaderOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'value' => $user->id,
                'label' => $user->name,
            ])
            ->values()
            ->all();
    }
}

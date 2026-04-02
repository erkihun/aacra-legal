<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\PersistUserAction;
use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'string'],
            'team_id' => ['nullable', 'string'],
            'role' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:1,0'],
            'sort' => ['nullable', 'in:name,email,created_at,last_login_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $users = User::query()
            ->with(['department', 'team', 'roles'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['department_id'] ?? null, fn ($query, string $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['team_id'] ?? null, fn ($query, string $teamId) => $query->where('team_id', $teamId))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (User $user): array => $this->userPayload($user));

        return Inertia::render('Admin/Users/Index', [
            'filters' => $filters,
            'users' => $users,
            'filterOptions' => $this->formOptions(),
            'can' => [
                'create' => $request->user()?->can('create', User::class) ?? false,
                'update' => $request->user()?->can('users.update') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'userItem' => null,
            'options' => $this->formOptions(),
            'localeOptions' => collect(LocaleCode::cases())->map(fn (LocaleCode $locale) => [
                'value' => $locale->value,
                'label' => $locale->label(),
            ])->values(),
            'canManageRoles' => $this->canManageRoles($request->user()),
        ]);
    }

    public function store(UserStoreRequest $request, PersistUserAction $action): RedirectResponse
    {
        $user = $action->execute(null, $request->validated(), $request->user());

        return to_route('users.show', $user)->with('success', __('User created successfully.'));
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load(['department', 'team', 'roles'])
            ->loadCount(['requestedAdvisories', 'assignedAdvisories', 'registeredCases', 'assignedCases']);

        return Inertia::render('Admin/Users/Show', [
            'userItem' => [
                ...$this->userPayload($user),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'stats' => [
                    'requested_advisories' => $user->requested_advisories_count,
                    'assigned_advisories' => $user->assigned_advisories_count,
                    'registered_cases' => $user->registered_cases_count,
                    'assigned_cases' => $user->assigned_cases_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $user) ?? false,
                'delete' => request()->user()?->can('delete', $user) ?? false,
            ],
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load(['department', 'team', 'roles']);

        return Inertia::render('Admin/Users/Edit', [
            'userItem' => $this->userPayload($user),
            'options' => $this->formOptions(),
            'localeOptions' => collect(LocaleCode::cases())->map(fn (LocaleCode $locale) => [
                'value' => $locale->value,
                'label' => $locale->label(),
            ])->values(),
            'canManageRoles' => $this->canManageRoles($request->user()),
            'canDelete' => $request->user()?->can('delete', $user) ?? false,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user, PersistUserAction $action): RedirectResponse
    {
        $action->execute($user, $request->validated(), $request->user());

        return to_route('users.show', $user)->with('success', __('User updated successfully.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()?->is($user)) {
            return back()->with('error', __('You cannot delete your own user account from user management.'));
        }

        if (
            $user->hasSystemRole(SystemRole::SUPER_ADMIN)
            && User::query()->role(SystemRole::SUPER_ADMIN->value)->whereKeyNot($user->getKey())->count() === 0
        ) {
            return back()->with('error', __('At least one Super Admin account must remain active.'));
        }

        $user->delete();

        return to_route('users.index')->with('success', __('User deleted successfully.'));
    }

    private function formOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('name_en')->get(['id', 'name_en', 'name_am', 'is_active']),
            'teams' => Team::query()->orderBy('name_en')->get(['id', 'name_en', 'name_am', 'type', 'is_active']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'employee_number' => $user->employee_number,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'avatar_url' => $user->avatarUrl(),
            'signature_url' => $user->signatureUrl(),
            'stamp_url' => $user->stampUrl(),
            'national_id' => User::formatNationalId($user->national_id),
            'telegram_username' => $user->telegram_username,
            'locale' => $user->locale?->value,
            'is_active' => $user->is_active,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name_en' => $user->department->name_en,
                'name_am' => $user->department->name_am,
            ] : null,
            'team' => $user->team ? [
                'id' => $user->team->id,
                'name_en' => $user->team->name_en,
                'name_am' => $user->team->name_am,
                'type' => $user->team->type?->value,
            ] : null,
            'roles' => $user->roles->pluck('name')->values(),
            'role_name' => $user->roles->pluck('name')->first(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function canManageRoles(?User $user): bool
    {
        return $user?->can('roles.manage') || $user?->can('users.assign_roles') || false;
    }
}

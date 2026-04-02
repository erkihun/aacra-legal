<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SyncRolePermissionsAction;
use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleStoreRequest;
use App\Http\Requests\Admin\RoleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->roleIndexPayload($role));

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Admin/Roles/Create', [
            'roleItem' => null,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(RoleStoreRequest $request, SyncRolePermissionsAction $action): RedirectResponse
    {
        $permissions = collect($request->validated('permissions', []))
            ->filter(fn (mixed $permission) => is_string($permission) && $permission !== '')
            ->values()
            ->all();

        $role = Role::query()->create([
            'name' => trim((string) $request->validated('name')),
            'guard_name' => 'web',
        ]);

        $action->execute($role, $permissions, $request->user());

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('role_created')
            ->withProperties([
                'role' => $role->name,
                'permissions' => $permissions,
            ])
            ->log(__('roles.created_success'));

        return to_route('roles.edit', $role)->with('success', __('roles.created_success'));
    }

    public function edit(Request $request, Role $role): Response
    {
        $this->authorizeManage($request);

        $role->load(['permissions', 'users']);

        return Inertia::render('Admin/Roles/Edit', [
            'roleItem' => $this->roleDetailPayload($role),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(
        RoleUpdateRequest $request,
        Role $role,
        SyncRolePermissionsAction $action,
    ): RedirectResponse {
        $validated = $request->validated();

        if ($this->isSystemRole($role) && $validated['name'] !== $role->name) {
            return back()->with('error', __('roles.system_name_locked_error'));
        }

        if ($role->name === SystemRole::SUPER_ADMIN->value) {
            return back()->with('error', __('roles.protected_permissions_error'));
        }

        $previousName = $role->name;
        $previousPermissions = $role->permissions()->pluck('name')->sort()->values()->all();
        $permissions = collect($validated['permissions'] ?? [])
            ->filter(fn (mixed $permission) => is_string($permission) && $permission !== '')
            ->values()
            ->all();

        $role->fill([
            'name' => trim((string) $validated['name']),
        ])->save();

        $action->execute($role, $permissions, $request->user());

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('role_updated')
            ->withProperties([
                'previous_name' => $previousName,
                'current_name' => $role->name,
                'previous_permissions' => $previousPermissions,
                'current_permissions' => $permissions,
            ])
            ->log(__('roles.updated_success'));

        return to_route('roles.edit', $role)->with('success', __('roles.updated_success'));
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeManage($request);

        if ($this->isSystemRole($role)) {
            return back()->with('error', __('roles.system_delete_error'));
        }

        if ($role->users()->exists()) {
            return back()->with('error', __('roles.in_use_delete_error'));
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->event('role_deleted')
            ->withProperties([
                'role' => $role->name,
            ])
            ->log(__('roles.deleted_success'));

        $role->delete();

        return to_route('roles.index')->with('success', __('roles.deleted_success'));
    }

    private function translatedPermissionGroup(string $group): string
    {
        $translation = __("permissions.groups.{$group}");

        return $translation === "permissions.groups.{$group}"
            ? str($group)->replace('-', ' ')->headline()->toString()
            : $translation;
    }

    private function translatedPermissionLabel(string $permission): string
    {
        $translation = __("permissions.labels.{$permission}");

        return $translation === "permissions.labels.{$permission}"
            ? str($permission)->after('.')->replace('-', ' ')->headline()->toString()
            : $translation;
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->can('roles.manage'), 403);
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString())
            ->map(fn ($group, string $key): array => [
                'key' => $key,
                'label' => $this->translatedPermissionGroup($key),
                'items' => $group->map(fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'label' => $this->translatedPermissionLabel($permission->name),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function roleIndexPayload(Role $role): array
    {
        return [
            'id' => (string) $role->getKey(),
            'name' => $role->name,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'created_at' => $role->created_at?->toIso8601String(),
            'is_system' => $this->isSystemRole($role),
            'permissions_locked' => $role->name === SystemRole::SUPER_ADMIN->value,
        ];
    }

    private function roleDetailPayload(Role $role): array
    {
        return [
            ...$this->roleIndexPayload($role),
            'permissions' => $role->permissions->pluck('name')->values(),
            'users' => $role->users->map(fn ($user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values(),
        ];
    }

    private function isSystemRole(Role $role): bool
    {
        return collect(SystemRole::cases())->contains(fn (SystemRole $systemRole) => $systemRole->value === $role->name);
    }
}

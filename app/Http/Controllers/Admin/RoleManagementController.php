<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SyncRolePermissionsAction;
use App\Enums\SystemRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolePermissionUpdateRequest;
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
        abort_unless($request->user()?->can('roles.manage'), 403);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => (string) $role->getKey(),
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->map(fn (array $role): array => [
                ...$role,
                'is_protected' => $role['name'] === SystemRole::SUPER_ADMIN->value,
            ]),
        ]);
    }

    public function edit(Request $request, Role $role): Response
    {
        abort_unless($request->user()?->can('roles.manage'), 403);

        $role->load(['permissions', 'users']);

        $permissionGroups = Permission::query()
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
            ->values();

        return Inertia::render('Admin/Roles/Edit', [
            'roleItem' => [
                'id' => (string) $role->getKey(),
                'name' => $role->name,
                'is_protected' => $role->name === SystemRole::SUPER_ADMIN->value,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users' => $role->users->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function update(
        RolePermissionUpdateRequest $request,
        Role $role,
        SyncRolePermissionsAction $action,
    ): RedirectResponse {
        if ($role->name === SystemRole::SUPER_ADMIN->value) {
            return back()->with('error', __('The Super Admin role permissions are managed by the system.'));
        }

        $action->execute($role, $request->validated('permissions', []), $request->user());

        return to_route('roles.edit', $role)->with('success', __('Role permissions updated successfully.'));
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
}

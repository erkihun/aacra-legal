<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePermissionDescriptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => $this->permissionPayload($permission))
            ->values();

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    public function edit(Request $request, Permission $permission): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Admin/Permissions/Edit', [
            'permissionItem' => $this->permissionPayload($permission),
        ]);
    }

    public function update(UpdatePermissionDescriptionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->forceFill([
            'description_en' => trim((string) $request->validated('description_en')),
            'description_am' => trim((string) $request->validated('description_am')),
        ])->save();

        activity()
            ->causedBy($request->user())
            ->performedOn($permission)
            ->event('permission_description_updated')
            ->withProperties([
                'permission' => $permission->name,
            ])
            ->log(__('permissions.updated_success'));

        return to_route('permissions.edit', $permission)->with('success', __('permissions.updated_success'));
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()?->can('roles.manage') || $request->user()?->can('users.assign_roles'),
            403,
        );
    }

    /**
     * @return array{id: int|string, name: string, group: string, group_label: string, label: string, description_en: string, description_am: string}
     */
    private function permissionPayload(Permission $permission): array
    {
        $descriptions = $this->catalogDescriptions($permission->name);
        $group = str($permission->name)->before('.')->toString();

        return [
            'id' => (string) $permission->getKey(),
            'name' => $permission->name,
            'group' => $group,
            'group_label' => $this->translatedPermissionGroup($group),
            'label' => $this->translatedPermissionLabel($permission->name),
            'description_en' => $permission->description_en ?: $descriptions['en'],
            'description_am' => $permission->description_am ?: $descriptions['am'],
        ];
    }

    /**
     * @return array{en: string, am: string}
     */
    private function catalogDescriptions(string $permission): array
    {
        /** @var array<string, array{en: string, am: string}> $catalog */
        $catalog = config('permission_descriptions', []);

        return $catalog[$permission] ?? [
            'en' => 'Description not configured.',
            'am' => 'መግለጫው አልተዋቀረም።',
        ];
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
            ? $permission
            : $translation;
    }
}

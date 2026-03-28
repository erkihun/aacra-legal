<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PersistUserAction
{
    public function execute(?User $user, array $attributes, User $actor): User
    {
        $user ??= new User;
        $roleName = $attributes['role_name'] ?? null;
        $actorCanManageRoles = $actor->can('roles.manage') || $actor->can('users.assign_roles');

        unset($attributes['role_name']);

        if (($attributes['password'] ?? null) === null || $attributes['password'] === '') {
            unset($attributes['password']);
        }

        $wasRecentlyCreated = ! $user->exists;
        $previousRoles = $user->exists ? $user->getRoleNames()->all() : [];
        $targetIsActive = (bool) ($attributes['is_active'] ?? $user->is_active);

        $this->guardSuperAdminIntegrity(
            user: $user,
            targetIsActive: $targetIsActive,
            roleName: $roleName,
            actorCanManageRoles: $actorCanManageRoles,
        );

        $user->fill($attributes);
        $user->save();

        if ($actorCanManageRoles) {
            $user->syncRoles($roleName !== null ? [$roleName] : []);

            if ($previousRoles !== $user->getRoleNames()->all()) {
                activity()
                    ->performedOn($user)
                    ->causedBy($actor)
                    ->event('roles_synced')
                    ->withProperties([
                        'previous_roles' => $previousRoles,
                        'current_roles' => $user->getRoleNames()->all(),
                    ])
                    ->log('User roles updated.');
            }
        }

        if ($wasRecentlyCreated) {
            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('created')
                ->log('User created.');
        }

        return $user->refresh()->loadMissing(['department', 'team', 'roles']);
    }

    private function guardSuperAdminIntegrity(
        User $user,
        bool $targetIsActive,
        ?string $roleName,
        bool $actorCanManageRoles,
    ): void {
        if (! $user->exists || ! $user->hasSystemRole(SystemRole::SUPER_ADMIN)) {
            return;
        }

        $targetWillRemainSuperAdmin = ! $actorCanManageRoles
            || $roleName === null
            || $roleName === SystemRole::SUPER_ADMIN->value;

        if ($targetIsActive && $targetWillRemainSuperAdmin) {
            return;
        }

        $otherActiveSuperAdmins = User::query()
            ->role(SystemRole::SUPER_ADMIN->value)
            ->whereKeyNot($user->getKey())
            ->where('is_active', true)
            ->count();

        if ($otherActiveSuperAdmins > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'is_active' => __('At least one Super Admin account must remain active.'),
        ]);
    }
}

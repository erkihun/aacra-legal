<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersistUserAction
{
    public function execute(?User $user, array $attributes, User $actor): User
    {
        $user ??= new User;
        $roleName = $attributes['role_name'] ?? null;
        $actorCanManageRoles = $actor->can('roles.manage') || $actor->can('users.assign_roles');
        $mediaUpdates = [
            'avatar_path' => $attributes['avatar'] ?? null,
            'signature_path' => $attributes['signature'] ?? null,
            'stamp_path' => $attributes['stamp'] ?? null,
        ];

        unset($attributes['role_name'], $attributes['avatar'], $attributes['signature'], $attributes['stamp']);

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

        $storedMedia = [];

        foreach ($mediaUpdates as $column => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $storedMedia[$column] = $this->storeMediaFile(
                $user,
                $file,
                str($column)->before('_path')->toString(),
                $user->getAttribute($column),
            );
        }

        if ($storedMedia !== []) {
            $user->fill($storedMedia);
            $user->save();
        }

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

    private function storeMediaFile(User $user, UploadedFile $file, string $prefix, mixed $oldPath): string
    {
        $extension = $file->extension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $prefix;
        $storedName = "{$prefix}-{$sanitizedName}-".Str::lower((string) Str::uuid()).".{$extension}";
        $directory = 'users/'.$user->getKey();
        $newPath = $file->storePubliclyAs($directory, $storedName, 'public');

        if (is_string($oldPath) && $oldPath !== '' && str_starts_with($oldPath, 'users/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
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

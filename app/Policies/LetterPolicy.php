<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;

class LetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAccessModule($user);
    }

    public function view(User $user, Letter $letter): bool
    {
        return $this->canAccessModule($user);
    }

    public function create(User $user): bool
    {
        return $user->can('letters.create');
    }

    public function update(User $user, Letter $letter): bool
    {
        return $user->can('letters.update');
    }

    public function delete(User $user, Letter $letter): bool
    {
        return $user->can('letters.delete');
    }

    public function preview(User $user, Letter $letter): bool
    {
        return $user->can('letters.preview');
    }

    public function print(User $user, Letter $letter): bool
    {
        return $user->can('letters.print');
    }

    public function approve(User $user, Letter $letter): bool
    {
        return $user->can('letters.approve');
    }

    private function canAccessModule(User $user): bool
    {
        return $user->canAnyPermissions([
            'letters.view',
            'letters.create',
            'letters.update',
            'letters.delete',
            'letters.preview',
            'letters.print',
            'letters.approve',
        ]);
    }
}

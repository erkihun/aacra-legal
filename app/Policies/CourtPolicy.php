<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courts.view') || $user->can('courts.manage');
    }

    public function view(User $user, Court $court): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('courts.manage');
    }

    public function update(User $user, Court $court): bool
    {
        return $user->can('courts.manage');
    }

    public function delete(User $user, Court $court): bool
    {
        return $user->can('courts.manage');
    }
}

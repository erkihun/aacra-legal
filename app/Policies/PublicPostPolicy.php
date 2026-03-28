<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PublicPost;
use App\Models\User;

class PublicPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('public-posts.view') || $user->can('public-posts.manage');
    }

    public function view(User $user, PublicPost $publicPost): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('public-posts.manage');
    }

    public function update(User $user, PublicPost $publicPost): bool
    {
        return $user->can('public-posts.manage');
    }

    public function delete(User $user, PublicPost $publicPost): bool
    {
        return $user->can('public-posts.manage');
    }

    public function publish(User $user, PublicPost $publicPost): bool
    {
        return $user->can('public-posts.manage');
    }
}

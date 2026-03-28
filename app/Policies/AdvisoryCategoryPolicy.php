<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvisoryCategory;
use App\Models\User;

class AdvisoryCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('advisory-categories.view') || $user->can('advisory-categories.manage');
    }

    public function view(User $user, AdvisoryCategory $advisoryCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('advisory-categories.manage');
    }

    public function update(User $user, AdvisoryCategory $advisoryCategory): bool
    {
        return $user->can('advisory-categories.manage');
    }

    public function delete(User $user, AdvisoryCategory $advisoryCategory): bool
    {
        return $user->can('advisory-categories.manage');
    }
}

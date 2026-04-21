<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ComplaintCategory;
use App\Models\User;

class ComplaintCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('complaint-categories.view') || $user->can('complaint-categories.manage');
    }

    public function view(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('complaint-categories.manage');
    }

    public function update(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $user->can('complaint-categories.manage');
    }

    public function delete(User $user, ComplaintCategory $complaintCategory): bool
    {
        return $user->can('complaint-categories.manage');
    }
}

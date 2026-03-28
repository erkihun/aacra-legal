<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('settings.manage');
    }
}

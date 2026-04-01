<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaseHearing;
use App\Models\User;

class CaseHearingPolicy
{
    public function view(User $user, CaseHearing $hearing): bool
    {
        return $user->can('view', $hearing->legalCase);
    }

    public function update(User $user, CaseHearing $hearing): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $hearing->recorded_by_id === $user->getKey()
            && $user->can('recordHearing', $hearing->legalCase);
    }

    public function delete(User $user, CaseHearing $hearing): bool
    {
        return $this->update($user, $hearing);
    }
}

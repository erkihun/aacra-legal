<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaseType;
use App\Models\User;

class CaseTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('legal-case-types.view') || $user->can('legal-case-types.manage');
    }

    public function view(User $user, CaseType $caseType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('legal-case-types.manage');
    }

    public function update(User $user, CaseType $caseType): bool
    {
        return $user->can('legal-case-types.manage');
    }

    public function delete(User $user, CaseType $caseType): bool
    {
        return $user->can('legal-case-types.manage');
    }
}

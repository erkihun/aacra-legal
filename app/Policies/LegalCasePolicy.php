<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SystemRole;
use App\Models\LegalCase;
use App\Models\User;

class LegalCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.view_any')
            || $user->can('cases.view_own')
            || $user->can('legal-cases.view');
    }

    public function view(User $user, LegalCase $legalCase): bool
    {
        if ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR) || $user->hasSystemRole(SystemRole::AUDITOR)) {
            return true;
        }

        if ($user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER)) {
            return $legalCase->assigned_team_leader_id === $user->getKey();
        }

        if ($user->hasSystemRole(SystemRole::REGISTRAR)) {
            return $legalCase->registered_by_id === $user->getKey();
        }

        if ($user->hasSystemRole(SystemRole::LEGAL_EXPERT)) {
            return $legalCase->assigned_legal_expert_id === $user->getKey();
        }

        return $legalCase->registered_by_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can('cases.create') || $user->can('legal-cases.create');
    }

    public function review(User $user, LegalCase $legalCase): bool
    {
        return ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR))
            && ($user->can('cases.review') || $user->can('legal-cases.review'));
    }

    public function assign(User $user, LegalCase $legalCase): bool
    {
        if ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR)) {
            return $user->can('cases.assign_team_leader') || $user->can('legal-cases.assign');
        }

        return $user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER)
            && ($user->can('cases.assign_expert') || $user->can('legal-cases.assign'))
            && $legalCase->assigned_team_leader_id === $user->getKey();
    }

    public function recordHearing(User $user, LegalCase $legalCase): bool
    {
        if ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER)) {
            return $user->can('cases.record_hearing') || $user->can('legal-cases.update');
        }

        return $legalCase->assigned_legal_expert_id === $user->getKey()
            && ($user->can('cases.record_hearing') || $user->can('legal-cases.update'));
    }

    public function close(User $user, LegalCase $legalCase): bool
    {
        return (
            $user->isSuperAdmin()
            || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR)
            || $user->hasSystemRole(SystemRole::LITIGATION_TEAM_LEADER)
        ) && ($user->can('cases.close') || $user->can('legal-cases.close'));
    }

    public function comment(User $user, LegalCase $legalCase): bool
    {
        return $user->can('comments.create') && $this->view($user, $legalCase);
    }

    public function attach(User $user, LegalCase $legalCase): bool
    {
        return $user->can('attachments.create') && $this->view($user, $legalCase);
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryRequest;
use App\Models\User;

class AdvisoryRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('advisory.view_any')
            || $user->can('advisory.view_own')
            || $user->can('advisory-requests.view');
    }

    public function view(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        if ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR) || $user->hasSystemRole(SystemRole::AUDITOR)) {
            return true;
        }

        if ($user->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER)) {
            return $advisoryRequest->assigned_team_leader_id === $user->getKey()
                || $advisoryRequest->assignedLegalExpert?->team_id === $user->team_id;
        }

        if ($user->hasSystemRole(SystemRole::LEGAL_EXPERT)) {
            return $advisoryRequest->assigned_legal_expert_id === $user->getKey();
        }

        return $advisoryRequest->requester_user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can('advisory.create') || $user->can('advisory-requests.create');
    }

    public function update(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        return $advisoryRequest->requester_user_id === $user->getKey()
            && $advisoryRequest->status === AdvisoryRequestStatus::RETURNED
            && ($user->can('advisory.create') || $user->can('advisory-requests.create'));
    }

    public function review(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        return ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR))
            && ($user->can('advisory.review') || $user->can('advisory-requests.review'));
    }

    public function assign(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        if ($user->isSuperAdmin() || $user->hasSystemRole(SystemRole::LEGAL_DIRECTOR)) {
            return $user->can('advisory.assign_team_leader') || $user->can('advisory-requests.assign');
        }

        return $user->hasSystemRole(SystemRole::ADVISORY_TEAM_LEADER)
            && ($user->can('advisory.assign_expert') || $user->can('advisory-requests.assign'))
            && $advisoryRequest->assigned_team_leader_id === $user->getKey();
    }

    public function respond(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        return ($user->isSuperAdmin() || $advisoryRequest->assigned_legal_expert_id === $user->getKey())
            && ($user->can('advisory.respond') || $user->can('advisory-requests.respond'));
    }

    public function comment(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        return $user->can('comments.create') && $this->view($user, $advisoryRequest);
    }

    public function attach(User $user, AdvisoryRequest $advisoryRequest): bool
    {
        return $user->can('attachments.create') && $this->view($user, $advisoryRequest);
    }
}

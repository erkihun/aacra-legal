<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvisoryResponse;
use App\Models\User;

class AdvisoryResponsePolicy
{
    public function view(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        $advisoryRequest = $advisoryResponse->advisoryRequest;

        if (! $user->can('view', $advisoryRequest)) {
            return false;
        }

        $isRequester = $advisoryRequest->requester_user_id === $user->getKey();

        if ($isRequester
            && ! $user->hasAdvisoryAdministrativeAccess()
            && ! $user->canLeadAdvisoryWorkflow()
            && $advisoryRequest->assigned_legal_expert_id !== $user->getKey()) {
            return ($advisoryResponse->approval_status ?? 'pending') === 'approved';
        }

        return true;
    }

    public function update(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $advisoryResponse->responder_id === $user->getKey()
            && ($user->can('advisory.respond') || $user->can('advisory-requests.respond'));
    }

    public function delete(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        return $this->update($user, $advisoryResponse);
    }

    public function approve(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        return $this->view($user, $advisoryResponse)
            && $user->can('advisory-responses.approve');
    }

    public function comment(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        return $this->view($user, $advisoryResponse)
            && $user->can('comments.create')
            && $user->can('advisory-responses.comment');
    }
}

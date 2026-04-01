<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvisoryResponse;
use App\Models\User;

class AdvisoryResponsePolicy
{
    public function view(User $user, AdvisoryResponse $advisoryResponse): bool
    {
        return $user->can('view', $advisoryResponse->advisoryRequest);
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
}

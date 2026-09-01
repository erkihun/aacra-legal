<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LawsuitRequestStatus;
use App\Models\LawsuitFilingRequest;
use App\Models\User;

class LawsuitFilingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lawsuit-requests.view') || $user->can('lawsuit-requests.review');
    }

    public function view(User $user, LawsuitFilingRequest $request): bool
    {
        if ($user->can('lawsuit-requests.review') || $user->hasGlobalCaseVisibility()) {
            return true;
        }

        return $request->created_by === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can('lawsuit-requests.create');
    }

    public function update(User $user, LawsuitFilingRequest $request): bool
    {
        return $request->created_by === $user->getKey()
            && in_array($request->status, [
                LawsuitRequestStatus::SUBMITTED,
                LawsuitRequestStatus::RETURNED,
            ], true)
            && $user->can('lawsuit-requests.create');
    }

    public function delete(User $user, LawsuitFilingRequest $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $request->created_by === $user->getKey()
            && $request->status === LawsuitRequestStatus::SUBMITTED
            && $user->can('lawsuit-requests.delete');
    }

    public function review(User $user, LawsuitFilingRequest $request): bool
    {
        return $user->can('lawsuit-requests.review');
    }

    public function attach(User $user, LawsuitFilingRequest $request): bool
    {
        return $user->can('attachments.create') && $this->view($user, $request);
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ComplaintResponse;
use App\Models\User;

class ComplaintResponsePolicy
{
    public function view(User $user, ComplaintResponse $complaintResponse): bool
    {
        return $user->can('view', $complaintResponse->complaint);
    }

    public function update(User $user, ComplaintResponse $complaintResponse): bool
    {
        return $user->can('respondDepartment', $complaintResponse->complaint)
            && $complaintResponse->responder_id === $user->getKey();
    }

    public function delete(User $user, ComplaintResponse $complaintResponse): bool
    {
        return $this->update($user, $complaintResponse) || $user->isSuperAdmin();
    }
}

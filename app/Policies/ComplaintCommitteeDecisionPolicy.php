<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ComplaintCommitteeDecision;
use App\Models\User;

class ComplaintCommitteeDecisionPolicy
{
    public function view(User $user, ComplaintCommitteeDecision $complaintCommitteeDecision): bool
    {
        return $user->can('view', $complaintCommitteeDecision->complaint);
    }

    public function update(User $user, ComplaintCommitteeDecision $complaintCommitteeDecision): bool
    {
        return $user->can('complaints.committee.decide')
            && $complaintCommitteeDecision->committee_actor_id === $user->getKey();
    }
}

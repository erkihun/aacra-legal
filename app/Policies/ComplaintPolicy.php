<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('complaints.view')
            || $user->can('complaints.view_all')
            || $user->can('complaints.view_department')
            || $user->can('complaints.view_own');
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isSuperAdmin() || $user->can('complaints.view_all')) {
            return true;
        }

        if (($user->can('complaints.committee.review') || $user->can('complaints.committee.decide')) && $complaint->is_escalated) {
            return true;
        }

        if ($user->can('complaints.view_department') && $complaint->department_id === $user->department_id) {
            return true;
        }

        return ($user->can('complaints.view_own') || $user->can('complaints.create'))
            && $complaint->complainant_user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->can('complaints.create');
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if ($complaint->isClosed()) {
            return false;
        }

        return $complaint->complainant_user_id === $user->getKey()
            && $user->can('complaints.create')
            && in_array($complaint->status, [
                ComplaintStatus::SUBMITTED,
                ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
            ], true);
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->isSuperAdmin() || $this->update($user, $complaint);
    }

    public function respondDepartment(User $user, Complaint $complaint): bool
    {
        if ($complaint->isClosed() || $complaint->is_escalated) {
            return false;
        }

        return $user->can('complaints.respond_department')
            && $user->department_id === $complaint->department_id;
    }

    public function forwardToCommittee(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.forward_to_committee')
            && $complaint->complainant_user_id === $user->getKey()
            && $complaint->canForwardToCommittee();
    }

    public function reviewCommittee(User $user, Complaint $complaint): bool
    {
        return ($user->can('complaints.committee.review') || $user->can('complaints.committee.decide'))
            && $complaint->is_escalated;
    }

    public function decideCommittee(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.committee.decide') && $complaint->is_escalated;
    }

    public function attach(User $user, Complaint $complaint): bool
    {
        return ! $complaint->isClosed()
            && $user->can('attachments.create')
            && $this->view($user, $complaint);
    }

    public function manageSettings(User $user): bool
    {
        return $user->can('complaints.settings.manage');
    }

    public function viewReports(User $user): bool
    {
        return $user->can('complaints.reports.view');
    }
}

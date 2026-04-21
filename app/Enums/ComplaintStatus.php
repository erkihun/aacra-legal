<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintStatus: string
{
    case SUBMITTED = 'submitted';
    case ASSIGNED_TO_DEPARTMENT = 'assigned_to_department';
    case IN_DEPARTMENT_REVIEW = 'in_department_review';
    case DEPARTMENT_RESPONDED = 'department_responded';
    case ESCALATED_TO_COMMITTEE = 'escalated_to_committee';
    case UNDER_COMMITTEE_REVIEW = 'under_committee_review';
    case COMMITTEE_DECIDED = 'committee_decided';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function labelKey(): string
    {
        return "status.{$this->value}";
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum AdvisoryRequestStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_DIRECTOR_REVIEW = 'under_director_review';
    case RETURNED = 'returned';
    case REJECTED = 'rejected';
    case ASSIGNED_TO_TEAM_LEADER = 'assigned_to_team_leader';
    case ASSIGNED_TO_EXPERT = 'assigned_to_expert';
    case IN_PROGRESS = 'in_progress';
    case RESPONDED = 'responded';
    case CLOSED = 'closed';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseStatus: string
{
    case INTAKE = 'intake';
    case UNDER_DIRECTOR_REVIEW = 'under_director_review';
    case ASSIGNED_TO_TEAM_LEADER = 'assigned_to_team_leader';
    case ASSIGNED_TO_EXPERT = 'assigned_to_expert';
    case IN_PROGRESS = 'in_progress';
    case AWAITING_DECISION = 'awaiting_decision';
    case DECIDED = 'decided';
    case APPEALED = 'appealed';
    case CLOSED = 'closed';
    case REJECTED = 'rejected';
}

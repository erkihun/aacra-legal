<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintCommitteeOutcome: string
{
    case UPHELD = 'upheld';
    case REJECTED = 'rejected';
    case PARTIALLY_UPHELD = 'partially_upheld';
    case RESOLVED_WITH_ACTION = 'resolved_with_action';
    case RETURNED_FOR_DEPARTMENT_FOLLOW_UP = 'returned_for_department_follow_up';
}

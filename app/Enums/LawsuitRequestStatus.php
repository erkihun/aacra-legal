<?php

declare(strict_types=1);

namespace App\Enums;

enum LawsuitRequestStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case RETURNED = 'returned';
}

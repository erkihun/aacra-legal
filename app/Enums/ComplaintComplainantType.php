<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintComplainantType: string
{
    case BRANCH_EMPLOYEE = 'branch_employee';
    case HEAD_OFFICE_EMPLOYEE = 'head_office_employee';
    case CLIENT = 'client';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemRole: string
{
    case SUPER_ADMIN = 'Super Admin';
    case LEGAL_DIRECTOR = 'Legal Director';
    case LITIGATION_TEAM_LEADER = 'Litigation Team Leader';
    case ADVISORY_TEAM_LEADER = 'Advisory Team Leader';
    case LEGAL_EXPERT = 'Legal Expert';
    case DEPARTMENT_REQUESTER = 'Department Requester';
    case REGISTRAR = 'Registrar';
    case AUDITOR = 'Auditor';
}

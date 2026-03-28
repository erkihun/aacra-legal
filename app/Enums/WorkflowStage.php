<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkflowStage: string
{
    case REQUESTER = 'requester';
    case REGISTRAR = 'registrar';
    case DIRECTOR = 'director';
    case TEAM_LEADER = 'team_leader';
    case EXPERT = 'expert';
    case COMPLETED = 'completed';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PriorityLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}

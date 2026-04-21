<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintEscalationType: string
{
    case AUTO = 'auto';
    case DISSATISFACTION = 'dissatisfaction';
}

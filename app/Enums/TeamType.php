<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamType: string
{
    case LITIGATION = 'litigation';
    case ADVISORY = 'advisory';
    case ADMINISTRATION = 'administration';
}

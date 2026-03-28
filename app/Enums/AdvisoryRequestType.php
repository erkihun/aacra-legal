<?php

declare(strict_types=1);

namespace App\Enums;

enum AdvisoryRequestType: string
{
    case WRITTEN = 'written';
    case VERBAL = 'verbal';
}

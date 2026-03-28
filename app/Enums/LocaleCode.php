<?php

declare(strict_types=1);

namespace App\Enums;

enum LocaleCode: string
{
    case ENGLISH = 'en';
    case AMHARIC = 'am';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::AMHARIC => 'አማርኛ',
        };
    }
}

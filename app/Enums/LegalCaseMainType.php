<?php

declare(strict_types=1);

namespace App\Enums;

enum LegalCaseMainType: string
{
    case CIVIL_LAW = 'civil-law';
    case CRIME = 'crime';
    case LABOUR_DISPUTE = 'labour-dispute';

    public static function inferFromCaseTypeCode(?string $code): self
    {
        return match (strtoupper((string) $code)) {
            'LAB' => self::LABOUR_DISPUTE,
            default => self::CIVIL_LAW,
        };
    }
}

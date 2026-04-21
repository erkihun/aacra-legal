<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemSettingGroup: string
{
    case GENERAL = 'general';
    case ORGANIZATION = 'organization';
    case LOCALIZATION = 'localization';
    case NOTIFICATIONS = 'notifications';
    case EMAIL = 'email';
    case SMS = 'sms';
    case TELEGRAM = 'telegram';
    case SECURITY = 'security';
    case APPEARANCE = 'appearance';
    case PUBLIC_WEBSITE = 'public_website';
    case COMPLAINTS = 'complaints';

    public function labelKey(): string
    {
        return match ($this) {
            self::GENERAL => 'settings.groups.general',
            self::ORGANIZATION => 'settings.groups.organization',
            self::LOCALIZATION => 'settings.groups.localization',
            self::NOTIFICATIONS => 'settings.groups.notifications',
            self::EMAIL => 'settings.groups.email',
            self::SMS => 'settings.groups.sms',
            self::TELEGRAM => 'settings.groups.telegram',
            self::SECURITY => 'settings.groups.security',
            self::APPEARANCE => 'settings.groups.appearance',
            self::PUBLIC_WEBSITE => 'settings.groups.public_website',
            self::COMPLAINTS => 'settings.groups.complaints',
        };
    }
}

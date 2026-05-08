<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use Throwable;

class LocalizedDateFormatter
{
    public function formatDate(
        DateTimeInterface|string|null $value,
        ?string $locale = null,
        ?string $timezone = null,
        string $fallback = '',
    ): string {
        return $this->format($value, $locale, $timezone, false, $fallback);
    }

    public function formatDateTime(
        DateTimeInterface|string|null $value,
        ?string $locale = null,
        ?string $timezone = null,
        string $fallback = '',
    ): string {
        return $this->format($value, $locale, $timezone, true, $fallback);
    }

    private function format(
        DateTimeInterface|string|null $value,
        ?string $locale,
        ?string $timezone,
        bool $includeTime,
        string $fallback,
    ): string {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $resolvedLocale = $locale ?: app()->getLocale();
        $resolvedTimezone = $timezone ?: (string) config('app.timezone', 'Africa/Addis_Ababa');
        $dateTime = $this->normalizeValue($value, $resolvedTimezone, $includeTime);

        if ($dateTime === null) {
            return is_string($value) && $fallback === '' ? $value : $fallback;
        }

        if ($resolvedLocale === 'am') {
            return $this->formatEthiopic($dateTime, $resolvedTimezone, $includeTime, $fallback);
        }

        return $includeTime
            ? $dateTime->setTimezone($resolvedTimezone)->format('Y-m-d H:i')
            : $dateTime->setTimezone($resolvedTimezone)->format('Y-m-d');
    }

    private function normalizeValue(
        DateTimeInterface|string $value,
        string $timezone,
        bool $includeTime,
    ): ?CarbonImmutable {
        try {
            if ($value instanceof DateTimeInterface) {
                return CarbonImmutable::instance($value)->setTimezone($timezone);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                return CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$value} 12:00:00", $timezone);
            }

            $parsed = CarbonImmutable::parse($value);

            return $includeTime
                ? $parsed->setTimezone($timezone)
                : $parsed->setTimezone($timezone);
        } catch (Throwable) {
            return null;
        }
    }

    private function formatEthiopic(
        CarbonImmutable $value,
        string $timezone,
        bool $includeTime,
        string $fallback,
    ): string {
        $dateFormatter = new IntlDateFormatter(
            'am_ET@calendar=ethiopic',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $timezone,
            IntlDateFormatter::TRADITIONAL,
            'd MMMM y',
        );

        $formattedDate = $dateFormatter->format($value);

        if (! is_string($formattedDate) || $formattedDate === '') {
            return $fallback;
        }

        if (! $includeTime) {
            return $formattedDate;
        }

        $timeFormatter = new IntlDateFormatter(
            'am_ET',
            IntlDateFormatter::NONE,
            IntlDateFormatter::SHORT,
            $timezone,
            IntlDateFormatter::GREGORIAN,
            'HH:mm',
        );

        $formattedTime = $timeFormatter->format($value);

        if (! is_string($formattedTime) || $formattedTime === '') {
            return $formattedDate;
        }

        return "{$formattedDate} {$formattedTime}";
    }
}

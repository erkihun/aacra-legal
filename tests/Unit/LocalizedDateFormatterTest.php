<?php

declare(strict_types=1);

use App\Support\LocalizedDateFormatter;
use Tests\TestCase;

uses(TestCase::class);

it('formats ethiopic dates for amharic locale', function (): void {
    $formatted = app(LocalizedDateFormatter::class)->formatDate('2026-05-08', 'am', 'Africa/Addis_Ababa');

    expect($formatted)
        ->toContain('2018')
        ->not->toBe('2026-05-08');
});

it('preserves gregorian dates for english locale', function (): void {
    $formatted = app(LocalizedDateFormatter::class)->formatDate('2026-05-08', 'en', 'Africa/Addis_Ababa');

    expect($formatted)->toBe('2026-05-08');
});

it('returns the provided fallback for null or invalid values', function (): void {
    $formatter = app(LocalizedDateFormatter::class);

    expect($formatter->formatDate(null, 'am', 'Africa/Addis_Ababa', '-'))->toBe('-')
        ->and($formatter->formatDate('not-a-date', 'am', 'Africa/Addis_Ababa', '-'))->toBe('-');
});

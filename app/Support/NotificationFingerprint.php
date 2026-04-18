<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

final class NotificationFingerprint
{
    public static function fromDatabaseNotification(DatabaseNotification $notification): string
    {
        $data = $notification->data;
        $type = (string) ($data['type'] ?? $notification->type);

        $context = [
            'advisory_request_id' => self::stringOrNull($data['advisory_request_id'] ?? null),
            'advisory_response_id' => self::stringOrNull($data['advisory_response_id'] ?? null),
            'legal_case_id' => self::stringOrNull($data['legal_case_id'] ?? null),
            'request_number' => self::stringOrNull($data['request_number'] ?? null),
            'case_number' => self::stringOrNull($data['case_number'] ?? null),
            'due_date' => self::stringOrNull($data['due_date'] ?? null),
            'next_hearing_date' => self::stringOrNull($data['next_hearing_date'] ?? null),
            'appeal_deadline' => self::stringOrNull($data['appeal_deadline'] ?? null),
            'responded_at' => self::stringOrNull($data['responded_at'] ?? null),
            'url' => self::stringOrNull($data['url'] ?? null),
            'title' => self::stringOrNull($data['title'] ?? null),
        ];

        if (self::allContextValuesNull($context)) {
            $context['created_at'] = $notification->created_at?->toIso8601String();
        }

        ksort($context);

        $segments = ["type:{$type}"];

        foreach ($context as $key => $value) {
            if ($value !== null) {
                $segments[] = "{$key}:{$value}";
            }
        }

        return implode('|', $segments);
    }

    /**
     * @template T of DatabaseNotification
     *
     * @param  Collection<int, T>  $notifications
     * @return Collection<int, T>
     */
    public static function deduplicate(Collection $notifications): Collection
    {
        return $notifications
            ->unique(fn (DatabaseNotification $notification): string => self::fromDatabaseNotification($notification))
            ->values();
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @param  array<string, string|null>  $context
     */
    private static function allContextValuesNull(array $context): bool
    {
        foreach ($context as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }
}

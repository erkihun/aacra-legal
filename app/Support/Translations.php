<?php

declare(strict_types=1);

namespace App\Support;

class Translations
{
    /**
     * @return array<string, string>
     */
    public static function forLocale(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, string>|null $translations */
        $translations = json_decode((string) file_get_contents($path), true);

        return $translations ?? [];
    }
}

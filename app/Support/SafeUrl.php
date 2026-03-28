<?php

declare(strict_types=1);

namespace App\Support;

final class SafeUrl
{
    public static function appRelativePath(?string $value, bool $allowAnchors = true): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '' || preg_match('/[\x00-\x1F\x7F]/', $normalized) === 1) {
            return null;
        }

        if ($allowAnchors && preg_match('/^#[A-Za-z0-9_-]+$/', $normalized) === 1) {
            return $normalized;
        }

        if (preg_match('/^[A-Za-z][A-Za-z0-9+\-.]*:/', $normalized) === 1) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $appPort = parse_url((string) config('app.url'), PHP_URL_PORT);
            $parts = parse_url($normalized);

            if (
                ! is_array($parts)
                || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
                || ! is_string($appHost)
                || ! isset($parts['host'])
                || ! hash_equals(strtolower($appHost), strtolower((string) $parts['host']))
            ) {
                return null;
            }

            if (isset($parts['port']) && $appPort !== false && (int) $parts['port'] !== (int) $appPort) {
                return null;
            }

            $path = (string) ($parts['path'] ?? '/');

            if (! str_starts_with($path, '/')) {
                return null;
            }

            return $path
                .(isset($parts['query']) ? '?'.$parts['query'] : '')
                .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        }

        if (! str_starts_with($normalized, '/')) {
            return null;
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $allowedPrefixes
     */
    public static function storageAssetPath(?string $value, array $allowedPrefixes): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = ltrim(trim($value), '/');

        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || str_contains($normalized, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $normalized) === 1
            || preg_match('/^[A-Za-z][A-Za-z0-9+\-.]*:/', $normalized) === 1
        ) {
            return null;
        }

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return $normalized;
            }
        }

        return null;
    }
}

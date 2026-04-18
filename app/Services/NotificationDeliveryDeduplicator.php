<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;

class NotificationDeliveryDeduplicator
{
    public function __construct(
        private readonly Repository $cache,
    ) {
    }

    public function has(string $fingerprint): bool
    {
        return $this->cache->has($this->cacheKey($fingerprint));
    }

    public function remember(string $fingerprint, int $ttlSeconds = 2_592_000): void
    {
        $this->cache->put($this->cacheKey($fingerprint), true, $ttlSeconds);
    }

    private function cacheKey(string $fingerprint): string
    {
        return 'notification-delivery:'.sha1($fingerprint);
    }
}

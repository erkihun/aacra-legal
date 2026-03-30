<?php

declare(strict_types=1);

namespace App\Services\Telegram;

final class TelegramSendResult
{
    private function __construct(
        public readonly bool $sent,
        public readonly ?string $error = null,
        public readonly bool $retryable = false,
    ) {}

    public static function sent(): self
    {
        return new self(true);
    }

    public static function failed(?string $error = null, bool $retryable = false): self
    {
        return new self(false, $error, $retryable);
    }
}

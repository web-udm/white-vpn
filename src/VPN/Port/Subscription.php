<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class Subscription
{
    public function __construct(
        public bool $enabled,
        public int $expiryTime,
        public int $uploadBytes,
        public int $downloadBytes,
    ) {
    }

    public function isExpired(): bool
    {
        if ($this->expiryTime === 0) {
            return false;
        }

        return $this->expiryTime < time() * 1000;
    }

    public function remainingDays(): ?int
    {
        if ($this->expiryTime === 0) {
            return null;
        }

        $diff = $this->expiryTime - time() * 1000;

        if ($diff <= 0) {
            return 0;
        }

        return (int) ceil($diff / (1000 * 60 * 60 * 24));
    }

    public function expiryDate(): ?\DateTimeImmutable
    {
        if ($this->expiryTime === 0) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp((int) ($this->expiryTime / 1000));
    }
}
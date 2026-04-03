<?php

declare(strict_types=1);

namespace App\Subscription\Port;

final readonly class SubscriptionStatus
{
    public function __construct(
        public string $status,
        public ?\DateTimeImmutable $expiresAt,
    ) {
    }
}

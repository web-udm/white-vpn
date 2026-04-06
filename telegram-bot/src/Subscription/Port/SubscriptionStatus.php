<?php

declare(strict_types=1);

namespace App\Subscription\Port;

final readonly class SubscriptionStatus
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_PAUSED = 'paused';
    public const string STATUS_EXPIRED = 'expired';

    public function __construct(
        public string $status,
        public ?\DateTimeImmutable $expiresAt,
    ) {
    }
}

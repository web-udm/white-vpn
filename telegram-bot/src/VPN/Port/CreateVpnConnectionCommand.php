<?php

declare(strict_types=1);

namespace App\VPN\Port;

use DateTimeImmutable;

final readonly class CreateVpnConnectionCommand
{
    public function __construct(
        public int $subscriptionId,
        public int $limitIp,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class CreateVpnConnectionCommand
{
    public function __construct(
        public int $subscriptionId,
        public string $subId,
        public string $protocol,
        public int $maxDevices,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}

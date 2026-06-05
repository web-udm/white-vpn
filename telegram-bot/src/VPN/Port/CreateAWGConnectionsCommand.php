<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class CreateAWGConnectionsCommand
{
    public function __construct(
        public int $subscriptionId,
    ) {
    }
}

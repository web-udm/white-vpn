<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class CreateClientCommand
{
    public function __construct(
        public string $subID,
        public int $limitIP = 3,
        public int $expiryTimestamp = 0,
    ) {
    }
}
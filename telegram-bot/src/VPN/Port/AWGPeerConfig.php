<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class AWGPeerConfig
{
    public function __construct(
        public string $filename,
        public string $configContent,
        public string $qrCodePng,
    ) {
    }
}

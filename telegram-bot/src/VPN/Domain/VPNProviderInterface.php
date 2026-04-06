<?php

declare(strict_types=1);

namespace App\VPN\Domain;

interface VPNProviderInterface
{
    public function createClient(string $subId, int $limitIp, int $expiryTimestamp): void;

    public function getConnectionURL(string $subId): string;
}

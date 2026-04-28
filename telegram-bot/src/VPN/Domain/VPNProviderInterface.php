<?php

declare(strict_types=1);

namespace App\VPN\Domain;

interface VPNProviderInterface
{
    public function createClient(string $subId, int $inboundId, int $limitIp, int $expiryTimestamp): void;

    /** @return int[] */
    public function getInboundIds(): array;

    public function getConnectionURL(string $subId): string;
}

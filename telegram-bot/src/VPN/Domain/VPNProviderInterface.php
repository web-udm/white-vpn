<?php

declare(strict_types=1);

namespace App\VPN\Domain;

interface VPNProviderInterface
{
    /** @param int[] $inboundIds */
    public function createClient(string $subId, array $inboundIds, int $limitIp, int $expiryTimestamp): void;

    /** @return int[] */
    public function getInboundIds(): array;

    public function getConnectionURL(string $subId): string;
}

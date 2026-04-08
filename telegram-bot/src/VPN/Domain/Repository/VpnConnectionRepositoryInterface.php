<?php

declare(strict_types=1);

namespace App\VPN\Domain\Repository;

use App\VPN\Domain\Entity\VpnConnection;

interface VpnConnectionRepositoryInterface
{
    public function save(VpnConnection $connection): void;

    public function findBySubscriptionAndProtocol(int $subscriptionId, string $protocol): ?VpnConnection;

    public function findActiveByUserIdAndProtocol(int $userId, string $protocol): ?VpnConnection;
}

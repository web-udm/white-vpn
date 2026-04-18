<?php

declare(strict_types=1);

namespace App\VPN\Domain\Repository;

use App\VPN\Domain\Entity\VpnConnection;

interface VpnConnectionRepositoryInterface
{
    public function save(VpnConnection $connection): void;

    /** @return VpnConnection[] */
    public function findAllActiveBySubscriptionId(int $subscriptionId): array;

    /** @return string[] */
    public function findAllActiveMtProxySecrets(): array;

    public function hasMtProxyConnection(int $subscriptionId): bool;
}

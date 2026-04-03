<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\VPNConnection;

interface VPNConnectionRepositoryInterface
{
    public function countActiveBySubscriptionId(int $subscriptionId): int;

    /** @return VPNConnection[] */
    public function findActiveBySubscriptionId(int $subscriptionId): array;

    /** @return VPNConnection[] */
    public function findBySubscriptionId(int $subscriptionId): array;

    public function save(VPNConnection $connection): void;
}

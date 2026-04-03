<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findActiveByUserId(int $userId): ?Subscription;

    /** @return Subscription[] */
    public function findExpiredActive(): array;

    public function save(Subscription $subscription): void;
}

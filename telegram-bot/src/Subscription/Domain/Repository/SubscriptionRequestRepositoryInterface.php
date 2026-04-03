<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\SubscriptionRequest;

interface SubscriptionRequestRepositoryInterface
{
    public function findPendingByTelegramId(int $telegramId): ?SubscriptionRequest;

    public function findById(int $id): ?SubscriptionRequest;

    public function save(SubscriptionRequest $request): void;
}

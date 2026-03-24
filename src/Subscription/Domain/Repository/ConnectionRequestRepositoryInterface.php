<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\ConnectionRequest;

interface ConnectionRequestRepositoryInterface
{
    public function findPendingByTelegramId(int $telegramId): ?ConnectionRequest;

    public function findById(int $id): ?ConnectionRequest;

    public function save(ConnectionRequest $request): void;
}

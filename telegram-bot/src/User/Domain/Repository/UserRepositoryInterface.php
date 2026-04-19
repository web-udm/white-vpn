<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\TelegramId;

interface UserRepositoryInterface
{
    public function findByTelegramId(TelegramId $telegramId): ?User;

    /** @return int[] */
    public function findAllTelegramIds(): array;

    public function save(User $user): void;
}
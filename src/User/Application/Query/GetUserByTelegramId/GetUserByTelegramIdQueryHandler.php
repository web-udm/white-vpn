<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUserByTelegramId;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\User\Port\GetUserByTelegramIDQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetUserByTelegramIdQueryHandler
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function __invoke(GetUserByTelegramIDQuery $query): ?User
    {
        return $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));
    }
}
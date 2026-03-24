<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\User\Port\RegisterUserCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RegisterUserCommandHandler
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        $telegramId = new TelegramId($command->telegramID);
        $existing = $this->userRepository->findByTelegramId($telegramId);

        if ($existing !== null) {
            return $existing;
        }

        $user = new User($telegramId);
        $this->userRepository->save($user);

        return $user;
    }
}

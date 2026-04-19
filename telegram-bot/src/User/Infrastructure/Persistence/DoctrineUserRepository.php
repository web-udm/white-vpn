<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserRepositoryInterface::class)]
final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findByTelegramId(TelegramId $telegramId): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'telegramId' => $telegramId->value,
        ]);
    }

    public function findAllTelegramIds(): array
    {
        /** @var array<array{telegramId: int}> $rows */
        $rows = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->select('u.telegramId')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'telegramId');
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
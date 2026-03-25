<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Persistence;

use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Domain\Repository\ConnectionRequestRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ConnectionRequestRepositoryInterface::class)]
final readonly class DoctrineConnectionRequestRepository implements ConnectionRequestRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findPendingByTelegramId(int $telegramId): ?ConnectionRequest
    {
        return $this->entityManager->getRepository(ConnectionRequest::class)->findOneBy([
            'telegramId' => $telegramId,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);
    }

    public function findById(int $id): ?ConnectionRequest
    {
        return $this->entityManager->find(ConnectionRequest::class, $id);
    }

    public function save(ConnectionRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }
}

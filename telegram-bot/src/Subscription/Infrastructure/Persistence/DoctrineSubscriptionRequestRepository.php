<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Persistence;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SubscriptionRequestRepositoryInterface::class)]
final readonly class DoctrineSubscriptionRequestRepository implements SubscriptionRequestRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findPendingByTelegramId(int $telegramId): ?SubscriptionRequest
    {
        return $this->entityManager->getRepository(SubscriptionRequest::class)->findOneBy([
            'telegramId' => $telegramId,
            'status' => SubscriptionRequest::STATUS_PENDING,
        ]);
    }

    public function findById(int $id): ?SubscriptionRequest
    {
        return $this->entityManager->find(SubscriptionRequest::class, $id);
    }

    public function save(SubscriptionRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }
}

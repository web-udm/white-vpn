<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Persistence;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SubscriptionRepositoryInterface::class)]
final readonly class DoctrineSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Subscription $subscription): void
    {
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    public function findActiveByUserId(int $userId): ?Subscription
    {
        return $this->entityManager->getRepository(Subscription::class)->findOneBy([
            'userId' => $userId,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}

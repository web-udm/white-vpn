<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Persistence;

use App\Subscription\Domain\Entity\VPNConnection;
use App\Subscription\Domain\Repository\VPNConnectionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VPNConnectionRepositoryInterface::class)]
final readonly class DoctrineVPNConnectionRepository implements VPNConnectionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function countActiveBySubscriptionId(int $subscriptionId): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(VPNConnection::class, 'c')
            ->join('c.subscription', 's')
            ->where('s.id = :subscriptionId')
            ->andWhere('c.status = :status')
            ->setParameter('subscriptionId', $subscriptionId)
            ->setParameter('status', VPNConnection::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveBySubscriptionId(int $subscriptionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(VPNConnection::class, 'c')
            ->join('c.subscription', 's')
            ->where('s.id = :subscriptionId')
            ->andWhere('c.status = :status')
            ->setParameter('subscriptionId', $subscriptionId)
            ->setParameter('status', VPNConnection::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function findBySubscriptionId(int $subscriptionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(VPNConnection::class, 'c')
            ->join('c.subscription', 's')
            ->where('s.id = :subscriptionId')
            ->setParameter('subscriptionId', $subscriptionId)
            ->getQuery()
            ->getResult();
    }

    public function save(VPNConnection $connection): void
    {
        $this->entityManager->persist($connection);
        $this->entityManager->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Persistence;

use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VpnConnectionRepositoryInterface::class)]
final readonly class DoctrineVpnConnectionRepository implements VpnConnectionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(VpnConnection $connection): void
    {
        $this->em->persist($connection);
        $this->em->flush();
    }

    public function findAllActiveBySubscriptionId(int $subscriptionId): array
    {
        return $this->em->getRepository(VpnConnection::class)->findBy([
            'subscriptionId' => $subscriptionId,
        ]);
    }

    public function findAllByType(string $type): array
    {
        return $this->em->getRepository(VpnConnection::class)->findBy(['type' => $type]);
    }
}

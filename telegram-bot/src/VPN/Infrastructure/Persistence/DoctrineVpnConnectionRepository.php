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

    public function findAllActiveMtProxySecrets(): array
    {
        $sql = "SELECT vc.external_id
                FROM vpn_connection vc
                JOIN subscription s ON s.id = vc.subscription_id
                WHERE vc.type = :type
                  AND s.status = :status";

        return $this->em->getConnection()->fetchFirstColumn($sql, [
            'type' => VpnConnection::TYPE_MTPROXY,
            'status' => 'active',
        ]);
    }

    public function hasMtProxyConnection(int $subscriptionId): bool
    {
        $count = $this->em->getRepository(VpnConnection::class)->count([
            'subscriptionId' => $subscriptionId,
            'type' => VpnConnection::TYPE_MTPROXY,
        ]);

        return $count > 0;
    }
}

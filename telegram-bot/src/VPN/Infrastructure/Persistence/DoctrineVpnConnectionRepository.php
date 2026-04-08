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

    public function findBySubscriptionAndProtocol(int $subscriptionId, string $protocol): ?VpnConnection
    {
        return $this->em->getRepository(VpnConnection::class)->findOneBy([
            'subscriptionId' => $subscriptionId,
            'protocol' => $protocol,
        ]);
    }

    public function findActiveByUserIdAndProtocol(int $userId, string $protocol): ?VpnConnection
    {
        $id = $this->em->getConnection()->fetchOne(
            'SELECT vc.id FROM vpn_connection vc
             JOIN subscription s ON s.id = vc.subscription_id
             WHERE s.user_id = :userId AND vc.protocol = :protocol AND vc.status = :status
             LIMIT 1',
            ['userId' => $userId, 'protocol' => $protocol, 'status' => VpnConnection::STATUS_ACTIVE],
        );

        if ($id === false) {
            return null;
        }

        return $this->em->find(VpnConnection::class, $id);
    }
}

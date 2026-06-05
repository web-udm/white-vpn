<?php

declare(strict_types=1);

namespace App\VPN\Application\Command\CreateAWGConnections;

use App\VPN\Domain\AWGProviderInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\CreateAWGConnectionsCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateAWGConnectionsCommandHandler
{
    private const int PEERS_PER_SUBSCRIPTION = 3;

    public function __construct(
        private AWGProviderInterface $awgProvider,
        private VpnConnectionRepositoryInterface $vpnConnectionRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateAWGConnectionsCommand $command): void
    {
        $existing = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $command->subscriptionId,
        );

        if (count($existing) > 0) {
            $this->logger->info('AWG connections already exist for subscription, skipping', [
                'subscription_id' => $command->subscriptionId,
                'existing_count'  => count($existing),
            ]);

            return;
        }

        for ($i = 0; $i < self::PEERS_PER_SUBSCRIPTION; $i++) {
            $this->createPeer($command->subscriptionId);
        }
    }

    private function createPeer(int $subscriptionId): void
    {
        $name   = $this->generateUuidV4();
        $peerId = $this->awgProvider->createPeer($name);

        $this->vpnConnectionRepository->save(new VpnConnection(
            subscriptionId: $subscriptionId,
            type: VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            externalId: $peerId,
        ));
    }

    private function generateUuidV4(): string
    {
        $data     = random_bytes(16);
        $data[6]  = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8]  = chr(ord($data[8]) & 0x3f | 0x80);

        return $data
                |> bin2hex(...)
                |> (fn($x) => str_split($x, 4))
                |> (fn($x) => vsprintf('%s%s-%s-%s-%s-%s%s%s', $x));
    }
}

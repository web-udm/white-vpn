<?php

declare(strict_types=1);

namespace App\VPN\Application\Command\CreateVpnConnection;

use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\CreateVpnConnectionCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateVpnConnectionCommandHandler
{
    public function __construct(
        private VPNProviderInterface $vpnProvider,
        private VpnConnectionRepositoryInterface $vpnConnectionRepository,
    ) {
    }

    public function __invoke(CreateVpnConnectionCommand $command): void
    {
        $expiryTimestamp = $command->expiresAt->getTimestamp() * 1000;

        $this->vpnProvider->createClient($command->subId, $command->limitIp, $expiryTimestamp);

        $connection = new VpnConnection(
            $command->subscriptionId,
            VpnConnection::TYPE_SUBSCRIPTION,
            $command->subId,
        );

        $this->vpnConnectionRepository->save($connection);
    }
}

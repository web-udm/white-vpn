<?php

declare(strict_types=1);

namespace App\VPN\Application\Command\CreateMtProxyConnection;

use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\CreateMtProxyConnectionCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateMtProxyConnectionCommandHandler
{
    public function __construct(
        private VpnConnectionRepositoryInterface $vpnConnectionRepository,
    ) {
    }

    public function __invoke(CreateMtProxyConnectionCommand $command): void
    {
        $secret = bin2hex(random_bytes(16));

        $connection = new VpnConnection(
            $command->subscriptionId,
            VpnConnection::TYPE_MTPROXY,
            $secret,
        );

        $this->vpnConnectionRepository->save($connection);
    }
}

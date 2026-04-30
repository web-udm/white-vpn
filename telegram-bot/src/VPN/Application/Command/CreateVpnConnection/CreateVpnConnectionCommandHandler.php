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
        $subId = self::generateSubId();
        $expiryTimestamp = $command->expiresAt->getTimestamp() * 1000;

        foreach ($this->vpnProvider->getInboundIds() as $inboundId) {
            $this->vpnProvider->createClient($subId, $inboundId, $command->limitIp, $expiryTimestamp);
        }

        $connection = new VpnConnection(
            $command->subscriptionId,
            VpnConnection::TYPE_SUBSCRIPTION,
            $subId,
        );

        $this->vpnConnectionRepository->save($connection);
    }

    private static function generateSubId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

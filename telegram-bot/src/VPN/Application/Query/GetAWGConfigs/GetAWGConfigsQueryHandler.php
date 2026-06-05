<?php

declare(strict_types=1);

namespace App\VPN\Application\Query\GetAWGConfigs;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\VPN\Domain\AWGProviderInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\AWGPeerConfig;
use App\VPN\Port\GetAWGConfigsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class GetAWGConfigsQueryHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly AWGProviderInterface $awgProvider,
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    /** @return AWGPeerConfig[] */
    public function __invoke(GetAWGConfigsQuery $query): array
    {
        /** @var ?SubscriptionStatus $status */
        $status = $this->handle(new GetActiveSubscriptionQuery($query->telegramId));

        if ($status === null || $status->status !== SubscriptionStatus::STATUS_ACTIVE) {
            return [];
        }

        $connections = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $status->subscriptionId,
        );

        $result = [];
        foreach ($connections as $index => $connection) {
            $configContent = $this->awgProvider->getPeerConfig($connection->getExternalId());
            $qrCodePng     = $this->awgProvider->generateQrPngFromConfig($configContent);

            $result[] = new AWGPeerConfig(
                filename:      sprintf('awg-%d.conf', $index + 1),
                configContent: $configContent,
                qrCodePng:     $qrCodePng,
            );
        }

        return $result;
    }
}

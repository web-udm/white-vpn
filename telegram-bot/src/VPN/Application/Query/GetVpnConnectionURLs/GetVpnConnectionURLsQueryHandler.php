<?php

declare(strict_types=1);

namespace App\VPN\Application\Query\GetVpnConnectionURLs;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\CreateMtProxyConnectionCommand;
use App\VPN\Port\GetVpnConnectionURLsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class GetVpnConnectionURLsQueryHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly VPNProviderInterface $vpnProvider,
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
        private readonly string $mtproxyHost,
        private readonly int $mtproxyPort,
    ) {
        $this->messageBus = $messageBus;
    }

    /** @return string[] */
    public function __invoke(GetVpnConnectionURLsQuery $query): array
    {
        /** @var ?SubscriptionStatus $status */
        $status = $this->handle(new GetActiveSubscriptionQuery($query->telegramId));

        if ($status === null || $status->status !== SubscriptionStatus::STATUS_ACTIVE) {
            return [];
        }

        $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($status->subscriptionId);

        if (!$this->hasMtProxy($connections)) {
            $this->messageBus->dispatch(new CreateMtProxyConnectionCommand($status->subscriptionId));
            $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($status->subscriptionId);
        }

        return array_map(
            fn ($connection) => $this->resolveURL($connection),
            $connections,
        );
    }

    /** @param VpnConnection[] $connections */
    private function hasMtProxy(array $connections): bool
    {
        foreach ($connections as $connection) {
            if ($connection->getType() === VpnConnection::TYPE_MTPROXY) {
                return true;
            }
        }

        return false;
    }

    private function resolveURL(VpnConnection $connection): string
    {
        if ($connection->getType() === VpnConnection::TYPE_MTPROXY) {
            return sprintf(
                'tg://proxy?server=%s&port=%d&secret=dd%s',
                $this->mtproxyHost,
                $this->mtproxyPort,
                $connection->getExternalId(),
            );
        }

        return $this->vpnProvider->getConnectionURL($connection->getExternalId());
    }
}

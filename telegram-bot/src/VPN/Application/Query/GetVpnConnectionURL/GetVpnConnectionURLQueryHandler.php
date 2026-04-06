<?php

declare(strict_types=1);

namespace App\VPN\Application\Query\GetVpnConnectionURL;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\GetVpnConnectionURLQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetVpnConnectionURLQueryHandler
{
    public function __construct(
        private VPNProviderInterface $vpnProvider,
        private VpnConnectionRepositoryInterface $vpnConnectionRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetVpnConnectionURLQuery $query): ?string
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramId));

        if ($user === null || $user->getId() === null) {
            return null;
        }

        $connection = $this->vpnConnectionRepository->findActiveByUserIdAndProtocol(
            $user->getId(),
            $query->protocol,
        );

        if ($connection === null) {
            return null;
        }

        return $this->vpnProvider->getConnectionURL($connection->getExternalId());
    }
}

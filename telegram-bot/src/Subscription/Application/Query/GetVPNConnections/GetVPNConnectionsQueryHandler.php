<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\GetVPNConnections;

use App\Subscription\Domain\Entity\VPNConnection;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\Repository\VPNConnectionRepositoryInterface;
use App\Subscription\Port\GetVPNConnectionsQuery;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetVPNConnectionsQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private VPNConnectionRepositoryInterface $connectionRepository,
    ) {
    }

    /**
     * @return VPNConnection[]
     */
    public function __invoke(GetVPNConnectionsQuery $query): array
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));

        if ($user === null) {
            return [];
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId() ?? 0);

        if ($subscription === null) {
            return [];
        }

        return $this->connectionRepository->findBySubscriptionId($subscription->getId() ?? 0);
    }
}

<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\GetActiveSubscription;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetActiveSubscriptionQueryHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetActiveSubscriptionQuery $query): ?SubscriptionStatus
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));

        if ($user === null || $user->getId() === null) {
            return null;
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId());

        if ($subscription === null) {
            return null;
        }

        return new SubscriptionStatus(
            status: $subscription->getStatus(),
            expiresAt: $subscription->getExpiresAt(),
        );
    }
}

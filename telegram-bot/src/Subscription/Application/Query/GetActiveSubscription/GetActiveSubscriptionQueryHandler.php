<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\GetActiveSubscription;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetActiveSubscriptionQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
    ) {
    }

    public function __invoke(GetActiveSubscriptionQuery $query): ?Subscription
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));

        if ($user === null) {
            return null;
        }

        return $this->subscriptionRepository->findActiveByUserId($user->getId() ?? 0);
    }
}

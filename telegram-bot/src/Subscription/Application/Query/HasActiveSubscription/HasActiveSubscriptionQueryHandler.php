<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\HasActiveSubscription;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class HasActiveSubscriptionQueryHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(HasActiveSubscriptionQuery $query): bool
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));

        if ($user === null || $user->getId() === null) {
            return false;
        }

        return $this->subscriptionRepository->findActiveByUserId($user->getId()) !== null;
    }
}

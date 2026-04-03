<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\HasPendingSubscriptionRequest;

use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\HasPendingSubscriptionRequestQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class HasPendingSubscriptionRequestQueryHandler
{
    public function __construct(
        private SubscriptionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(HasPendingSubscriptionRequestQuery $query): bool
    {
        return $this->requestRepository->findPendingByTelegramId($query->telegramID) !== null;
    }
}

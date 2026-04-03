<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\HasPendingConnectionRequest;

use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\HasPendingConnectionRequestQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class HasPendingConnectionRequestQueryHandler
{
    public function __construct(
        private SubscriptionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(HasPendingConnectionRequestQuery $query): bool
    {
        return $this->requestRepository->findPendingByTelegramId($query->telegramID) !== null;
    }
}

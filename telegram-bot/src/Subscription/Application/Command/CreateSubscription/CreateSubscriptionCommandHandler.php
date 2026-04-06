<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscription;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Port\CreateSubscriptionCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateSubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
    ) {
    }

    public function __invoke(CreateSubscriptionCommand $command): Subscription
    {
        $subscription = new Subscription($command->userId, $command->expiresAt, $command->isVip);
        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }
}

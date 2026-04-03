<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscriptionRequest;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\CreateSubscriptionRequestCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateSubscriptionRequestCommandHandler
{
    public function __construct(
        private SubscriptionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(CreateSubscriptionRequestCommand $command): SubscriptionRequest
    {
        $request = new SubscriptionRequest($command->telegramID);
        $this->requestRepository->save($request);

        return $request;
    }
}

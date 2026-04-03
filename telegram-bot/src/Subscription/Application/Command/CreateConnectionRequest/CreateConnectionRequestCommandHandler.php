<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateConnectionRequest;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\CreateConnectionRequestCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateConnectionRequestCommandHandler
{
    public function __construct(
        private SubscriptionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(CreateConnectionRequestCommand $command): SubscriptionRequest
    {
        $request = new SubscriptionRequest($command->telegramID);
        $this->requestRepository->save($request);

        return $request;
    }
}

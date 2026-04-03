<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\RejectSubscriptionRequest;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\SubscriptionRequestException;
use App\Subscription\Port\RejectSubscriptionRequestCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RejectSubscriptionRequestCommandHandler
{
    public function __construct(
        private SubscriptionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(RejectSubscriptionRequestCommand $command): void
    {
        $request = $this->findPendingRequest($command->requestID);

        $request->reject();
        $this->requestRepository->save($request);
    }

    private function findPendingRequest(int $requestId): SubscriptionRequest
    {
        $request = $this->requestRepository->findById($requestId);

        if ($request === null) {
            throw new SubscriptionRequestException('Request not found');
        }

        if (!$request->isPending()) {
            throw new SubscriptionRequestException('Request is not pending');
        }

        return $request;
    }
}

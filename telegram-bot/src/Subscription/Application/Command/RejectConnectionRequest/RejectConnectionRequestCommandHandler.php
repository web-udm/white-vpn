<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\RejectConnectionRequest;

use App\Subscription\Port\ConnectionRequestException;
use App\Subscription\Port\RejectConnectionRequestCommand;
use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Domain\Repository\ConnectionRequestRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RejectConnectionRequestCommandHandler
{
    public function __construct(
        private ConnectionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(RejectConnectionRequestCommand $command): void
    {
        $request = $this->findPendingRequest($command->requestID);

        $request->reject();
        $this->requestRepository->save($request);
    }

    private function findPendingRequest(int $requestId): ConnectionRequest
    {
        $request = $this->requestRepository->findById($requestId);

        if ($request === null) {
            throw new ConnectionRequestException('Request not found');
        }

        if (!$request->isPending()) {
            throw new ConnectionRequestException('Request is not pending');
        }

        return $request;
    }
}

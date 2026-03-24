<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateConnectionRequest;

use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Domain\Repository\ConnectionRequestRepositoryInterface;
use App\Subscription\Port\CreateConnectionRequestCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateConnectionRequestCommandHandler
{
    public function __construct(
        private ConnectionRequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(CreateConnectionRequestCommand $command): ConnectionRequest
    {
        $request = new ConnectionRequest($command->telegramID);
        $this->requestRepository->save($request);

        return $request;
    }
}

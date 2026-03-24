<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ApproveConnectionRequest;

use App\Subscription\Port\ApproveConnectionRequestCommand;
use App\Subscription\Port\ConnectionRequestException;
use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Domain\Repository\ConnectionRequestRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Port\CreateClientCommand;
use App\VPN\Port\GetSubscriptionURLQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ApproveConnectionRequestCommandHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly ConnectionRequestRepositoryInterface $requestRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(ApproveConnectionRequestCommand $command): string
    {
        $request = $this->findPendingRequest($command->requestID);
        $subID = $this->resolveSubID($request->getTelegramId());

        $this->handle(new CreateClientCommand($subID));

        $request->approve();
        $this->requestRepository->save($request);

        return $this->handle(new GetSubscriptionURLQuery($subID));
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

    private function resolveSubID(int $telegramId): string
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($telegramId));

        if ($user === null) {
            throw new ConnectionRequestException('User not found');
        }

        return $user->getSubId();
    }
}
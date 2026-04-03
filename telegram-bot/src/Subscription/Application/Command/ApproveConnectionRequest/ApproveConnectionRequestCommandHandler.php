<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ApproveConnectionRequest;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\ApproveConnectionRequestCommand;
use App\Subscription\Port\ConnectionRequestException;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ApproveConnectionRequestCommandHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly SubscriptionRequestRepositoryInterface $requestRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(ApproveConnectionRequestCommand $command): void
    {
        $request = $this->findPendingRequest($command->requestID);
        $userId = $this->resolveUserId($request->getTelegramId());

        $this->handle(new CreateSubscriptionCommand($userId));

        $request->approve();
        $this->requestRepository->save($request);
    }

    private function findPendingRequest(int $requestId): SubscriptionRequest
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

    private function resolveUserId(int $telegramId): int
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($telegramId));

        if ($user === null) {
            throw new ConnectionRequestException('User not found');
        }

        return $user->getId() ?? throw new ConnectionRequestException('User ID is null');
    }
}

<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ApproveSubscriptionRequest;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\ApproveSubscriptionRequestCommand;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\Subscription\Port\SubscriptionRequestException;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Port\CreateAWGConnectionsCommand;
use App\VPN\Port\CreateVpnConnectionCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ApproveSubscriptionRequestCommandHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly SubscriptionRequestRepositoryInterface $requestRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(ApproveSubscriptionRequestCommand $command): void
    {
        $request = $this->findPendingRequest($command->requestID);
        $user = $this->resolveUser($request->getTelegramId());
        $expiresAt = new \DateTimeImmutable('+30 days');

        /** @var Subscription $subscription */
        $subscription = $this->handle(new CreateSubscriptionCommand($user->getId(), $expiresAt));

        $subscriptionId = $subscription->getId() ?? throw new SubscriptionRequestException('Subscription has no ID');

        $this->messageBus->dispatch(new CreateVpnConnectionCommand(
            subscriptionId: $subscriptionId,
            limitIp: $subscription->isVip() ? 10 : 1,
            expiresAt: $expiresAt,
        ));

        $this->messageBus->dispatch(new CreateAWGConnectionsCommand(
            subscriptionId: $subscriptionId,
        ));

        $request->approve();
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

    private function resolveUser(int $telegramId): User
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($telegramId));

        if ($user === null) {
            throw new SubscriptionRequestException('User not found');
        }

        return $user;
    }
}

<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\GetVPNConnection;

use App\Subscription\Domain\Entity\VPNConnection;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\Repository\VPNConnectionRepositoryInterface;
use App\Subscription\Port\GetVPNConnectionCommand;
use App\Subscription\Port\SubscriptionException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Port\CreateClientCommand;
use App\VPN\Port\GetSubscriptionURLQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class GetVPNConnectionCommandHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly VPNConnectionRepositoryInterface $connectionRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(GetVPNConnectionCommand $command): string
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($command->telegramID));

        if ($user === null) {
            throw new SubscriptionException('User not found');
        }

        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId() ?? 0);

        if ($subscription === null) {
            throw new SubscriptionException('No active subscription');
        }

        $activeCount = $this->connectionRepository->countActiveBySubscriptionId($subscription->getId() ?? 0);

        if ($activeCount >= $subscription->getQuota()) {
            throw new SubscriptionException('Connection quota reached');
        }

        $subId = $this->generateSubId();

        $this->handle(new CreateClientCommand($subId));

        $connection = new VPNConnection($subscription, $subId);
        $this->connectionRepository->save($connection);

        return $this->handle(new GetSubscriptionURLQuery($subId));
    }

    private function generateSubId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

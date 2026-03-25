<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\HasActiveSubscription;

use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Port\GetSubscriptionStatusQuery;
use App\VPN\Port\Subscription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class HasActiveSubscriptionQueryHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(HasActiveSubscriptionQuery $query): bool
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($query->telegramID));

        if ($user === null) {
            return false;
        }

        return $this->isActive($user->getSubId());
    }

    private function isActive(string $subID): bool
    {
        try {
            /** @var ?Subscription $status */
            $status = $this->handle(new GetSubscriptionStatusQuery($subID));
        } catch (\Throwable) {
            return false;
        }

        return $status !== null && $status->enabled && !$status->isExpired();
    }
}
<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Port\CreateSubscriptionRequestCommand;
use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\Subscription\Port\HasPendingSubscriptionRequestQuery;
use App\Telegram\Infrastructure\Command\NotifyAdminNewRequest\NotifyAdminNewRequestCommand;
use App\User\Port\RegisterUserCommand;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class SubscriptionRequestHandler
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Nutgram $bot): void
    {
        /** @var int $telegramId guaranteed by middleware */
        $telegramId = $bot->userId();

        $this->handle(new RegisterUserCommand($telegramId));

        if ($this->hasPendingRequest($telegramId)) {
            $bot->answerCallbackQuery();
            $bot->sendMessage('Ваша заявка уже на рассмотрении. Ожидайте ответа.');
            return;
        }

        if ($this->hasActiveSubscription($telegramId)) {
            $bot->answerCallbackQuery();
            $bot->sendMessage('У вас уже есть активная подписка.');
            return;
        }

        $this->submitRequest($bot, $telegramId);
    }

    private function hasPendingRequest(int $telegramId): bool
    {
        return $this->handle(new HasPendingSubscriptionRequestQuery($telegramId));
    }

    private function hasActiveSubscription(int $telegramId): bool
    {
        return $this->handle(new HasActiveSubscriptionQuery($telegramId));
    }

    private function submitRequest(Nutgram $bot, int $telegramId): void
    {
        /** @var SubscriptionRequest $request */
        $request = $this->handle(new CreateSubscriptionRequestCommand($telegramId));

        $bot->answerCallbackQuery();
        $bot->sendMessage('Заявка на подключение отправлена! Ожидайте подтверждения от администратора.');

        if ($request->getId() !== null) {
            $this->messageBus->dispatch(new NotifyAdminNewRequestCommand($request->getId(), $telegramId));
        }
    }
}

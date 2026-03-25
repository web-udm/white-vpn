<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Port\CreateConnectionRequestCommand;
use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\Subscription\Port\HasPendingConnectionRequestQuery;
use App\Telegram\Infrastructure\Command\NotifyAdminNewRequest\NotifyAdminNewRequestCommand;
use App\User\Port\RegisterUserCommand;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConnectHandler
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

        $this->registerUser($telegramId);

        if ($this->hasPendingRequest($telegramId)) {
            $bot->answerCallbackQuery(text: 'Ваша заявка уже на рассмотрении. Ожидайте ответа.', show_alert: true);
            return;
        }

        if ($this->hasActiveSubscription($telegramId)) {
            $bot->answerCallbackQuery(text: 'У вас уже есть активное подключение!', show_alert: true);
            return;
        }

        $this->submitRequest($bot, $telegramId);
    }

    private function registerUser(int $telegramId): void
    {
        $this->handle(new RegisterUserCommand($telegramId));
    }

    private function hasPendingRequest(int $telegramId): bool
    {
        return $this->handle(new HasPendingConnectionRequestQuery($telegramId));
    }

    private function hasActiveSubscription(int $telegramId): bool
    {
        return $this->handle(new HasActiveSubscriptionQuery($telegramId));
    }

    private function submitRequest(Nutgram $bot, int $telegramId): void
    {
        /** @var ConnectionRequest $request */
        $request = $this->handle(new CreateConnectionRequestCommand($telegramId));

        $bot->answerCallbackQuery();
        $bot->sendMessage('Заявка на подключение отправлена! Ожидайте подтверждения от администратора.');

        if ($request->getId() !== null) {
            $this->messageBus->dispatch(new NotifyAdminNewRequestCommand($request->getId(), $telegramId));
        }
    }
}

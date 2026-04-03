<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Port\GetVPNConnectionCommand;
use App\Subscription\Port\SubscriptionException;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class GetVPNHandler
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

        try {
            /** @var string $subscriptionUrl */
            $subscriptionUrl = $this->handle(new GetVPNConnectionCommand($telegramId));
        } catch (HandlerFailedException $e) {
            $this->handleError($bot, $e->getPrevious() ?? $e);
            return;
        } catch (\Throwable $e) {
            $this->handleError($bot, $e);
            return;
        }

        $bot->sendMessage("Ваш конфиг готов!\n\nСсылка на подключение:\n{$subscriptionUrl}");
    }

    private function handleError(Nutgram $bot, \Throwable $e): void
    {
        $message = match (true) {
            $e instanceof SubscriptionException && $e->getMessage() === 'No active subscription'
                => 'У вас нет активной подписки. Нажмите «Подключиться» чтобы подать заявку.',
            $e instanceof SubscriptionException && $e->getMessage() === 'Connection quota reached'
                => 'Достигнут лимит подключений для вашей подписки.',
            default => 'Сервис временно недоступен, попробуйте позже.',
        };

        $bot->sendMessage($message);
    }
}

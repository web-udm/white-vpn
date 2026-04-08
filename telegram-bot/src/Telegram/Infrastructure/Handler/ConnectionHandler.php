<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\Telegram\Infrastructure\Keyboard\MainMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConnectionHandler
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

        $bot->answerCallbackQuery();

        /** @var ?SubscriptionStatus $status */
        $status = $this->handle(new GetActiveSubscriptionQuery($telegramId));

        if ($status === null || $status->status !== SubscriptionStatus::STATUS_ACTIVE) {
            $bot->sendMessage('У вас нет активной подписки. Нажмите «Подключиться» чтобы подать заявку.');
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('VLESS', callback_data: MainMenu::CONNECTION_VLESS),
            );

        $bot->sendMessage('Выберите протокол подключения:', reply_markup: $keyboard);
    }
}

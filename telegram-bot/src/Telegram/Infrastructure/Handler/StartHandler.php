<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\Infrastructure\Keyboard\MainMenu;
use App\User\Port\RegisterUserCommand;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class StartHandler
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(Nutgram $bot): void
    {
        /** @var int $telegramId guaranteed by middleware */
        $telegramId = $bot->userId();

        $this->messageBus->dispatch(new RegisterUserCommand($telegramId));

        $text = "Привет!\n\n"
            . "Это бот для подключения VPN. Вам доступен бесплатный пробный период — 7 дней\n\n"
            . "По всем вопросам: @moildar\n\n"
            . "Нажмите кнопку «Меню» внизу экрана, чтобы начать.";

        $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
            ->addRow(KeyboardButton::make(MainMenu::MENU_BUTTON_TEXT));

        $bot->sendMessage(text: $text, reply_markup: $keyboard);
    }
}

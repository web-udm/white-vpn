<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\Infrastructure\Keyboard\MainMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final readonly class StartHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $text = "Привет!\n\n"
            . "Это бот для подключения VPN. Вам доступен бесплатный пробный период — 7 дней\n\n"
            . "По всем вопросам: @moildar\n\n"
            . "Чтобы начать работу, нажмите «Подключиться»";

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(MainMenu::BUTTONS[MainMenu::CONNECT], callback_data: MainMenu::CONNECT),
            )
            ->addRow(
                InlineKeyboardButton::make(MainMenu::BUTTONS[MainMenu::STATUS], callback_data: MainMenu::STATUS),
                InlineKeyboardButton::make(MainMenu::BUTTONS[MainMenu::SUPPORT], callback_data: MainMenu::SUPPORT),
            );

        $bot->sendMessage(text: $text, reply_markup: $keyboard);
    }
}

<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command\NotifyAdminNewRequest;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class NotifyAdminNewRequestCommandHandler
{
    public function __construct(
        private Nutgram $bot,
        #[Autowire('%telegram.admin_id%')] private int $adminTelegramId,
    ) {
    }

    public function __invoke(NotifyAdminNewRequestCommand $command): void
    {
        if ($this->adminTelegramId === 0) {
            return;
        }

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('Одобрить', callback_data: "approve_{$command->requestId}"),
                InlineKeyboardButton::make('Отклонить', callback_data: "reject_{$command->requestId}"),
            );

        $this->bot->sendMessage(
            text: "Новая заявка на подключение!\n\nПользователь: {$command->telegramId}\nЗаявка: #{$command->requestId}",
            chat_id: $this->adminTelegramId,
            reply_markup: $keyboard,
        );
    }
}

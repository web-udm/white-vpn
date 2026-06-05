<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InstructionsHandler
{
    public function __construct(
        /** @var array<int, array{title: string, url: string}> */
        #[Autowire(param: 'app.instructions')] private array $instructions,
    ) {
    }

    public function __invoke(Nutgram $bot): void
    {
        if ($this->instructions === []) {
            $bot->sendMessage(text: 'Инструкции пока не добавлены.');

            return;
        }

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($this->instructions as $article) {
            $keyboard->addRow(
                InlineKeyboardButton::make($article['title'], url: $article['url']),
            );
        }

        $bot->sendMessage(text: '❓ F.A.Q.:', reply_markup: $keyboard);
    }
}

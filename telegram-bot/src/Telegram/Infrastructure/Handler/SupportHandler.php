<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use SergiX44\Nutgram\Nutgram;

final readonly class SupportHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();
        $bot->sendMessage("По всем вопросам пишите @moildar либо на почту: web.udm@gmail.com");
    }
}

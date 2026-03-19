<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use SergiX44\Nutgram\Nutgram;

final class StartHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessage('Привет!');
    }
}

<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use App\Telegram\Infrastructure\Handler\StartHandler;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;

final class NutgramFactory
{
    public static function create(string $token): Nutgram
    {
        if ($token === '') {
            throw new \RuntimeException('BOT_TOKEN is not set. Please configure it in .env');
        }

        $bot = new Nutgram($token, new Configuration());
        $bot->onMessage(new StartHandler());

        return $bot;
    }
}

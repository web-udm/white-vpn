<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use SergiX44\Nutgram\Hydrator\Hydrator;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Common\Update;

final class WebhookProcessor
{
    public function __construct(private readonly Nutgram $bot)
    {
    }

    public function process(string $payload): void
    {
        if ($payload === '') {
            return;
        }

        $hydrator = $this->bot->getContainer()->get(Hydrator::class);
        $update = $hydrator->hydrate(
            json_decode($payload, true, flags: JSON_THROW_ON_ERROR),
            Update::class,
        );

        $this->bot->processUpdate($update);
    }
}
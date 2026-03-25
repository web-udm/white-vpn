<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command\NotifyAdminNewRequest;

final readonly class NotifyAdminNewRequestCommand
{
    public function __construct(
        public int $requestId,
        public int $telegramId,
    ) {
    }
}

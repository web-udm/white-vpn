<?php

declare(strict_types=1);

namespace App\User\Port;

final readonly class GetUserByTelegramIDQuery
{
    public function __construct(public int $telegramID)
    {
    }
}
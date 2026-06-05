<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class GetAWGConfigsQuery
{
    public function __construct(
        public int $telegramId,
    ) {
    }
}

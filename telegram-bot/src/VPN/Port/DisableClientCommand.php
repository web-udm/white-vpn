<?php

declare(strict_types=1);

namespace App\VPN\Port;

final readonly class DisableClientCommand
{
    public function __construct(public string $subId)
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Subscription\Port;

final readonly class CreateSubscriptionCommand
{
    public function __construct(public int $userId)
    {
    }
}

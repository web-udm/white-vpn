<?php

declare(strict_types=1);

namespace App\Subscription\Port;

final readonly class RejectSubscriptionRequestCommand
{
    public function __construct(public int $requestID)
    {
    }
}
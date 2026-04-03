<?php

declare(strict_types=1);

namespace App\Subscription\Port;

final readonly class HasPendingSubscriptionRequestQuery
{
    public function __construct(public int $telegramID)
    {
    }
}
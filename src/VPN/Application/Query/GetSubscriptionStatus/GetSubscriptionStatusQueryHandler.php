<?php

declare(strict_types=1);

namespace App\VPN\Application\Query\GetSubscriptionStatus;

use App\VPN\Infrastructure\Xui\XuiVPNProvider;
use App\VPN\Port\GetSubscriptionStatusQuery;
use App\VPN\Port\Subscription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetSubscriptionStatusQueryHandler
{
    public function __construct(private XuiVPNProvider $vpnProvider)
    {
    }

    public function __invoke(GetSubscriptionStatusQuery $query): ?Subscription
    {
        return $this->vpnProvider->getSubscriptionStatus($query->subID);
    }
}
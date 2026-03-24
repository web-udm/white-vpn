<?php

declare(strict_types=1);

namespace App\VPN\Application\Query\GetSubscriptionURL;

use App\VPN\Infrastructure\Xui\XuiVPNProvider;
use App\VPN\Port\GetSubscriptionURLQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetSubscriptionURLQueryHandler
{
    public function __construct(private XuiVPNProvider $vpnProvider)
    {
    }

    public function __invoke(GetSubscriptionURLQuery $query): string
    {
        return $this->vpnProvider->getSubscriptionURL($query->subID);
    }
}
<?php

declare(strict_types=1);

namespace App\VPN\Application\Command\DisableClient;

use App\VPN\Infrastructure\Xui\XuiVPNProvider;
use App\VPN\Port\DisableClientCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DisableClientCommandHandler
{
    public function __construct(private XuiVPNProvider $vpnProvider)
    {
    }

    public function __invoke(DisableClientCommand $command): void
    {
        $this->vpnProvider->disableClient($command->subId);
    }
}

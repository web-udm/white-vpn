<?php

declare(strict_types=1);

namespace App\VPN\Application\Command\CreateClient;

use App\VPN\Infrastructure\Xui\XuiVPNProvider;
use App\VPN\Port\CreateClientCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateClientCommandHandler
{
    public function __construct(private XuiVPNProvider $vpnProvider)
    {
    }

    public function __invoke(CreateClientCommand $command): void
    {
        $this->vpnProvider->createClient(
            $command->subID,
            $command->limitIP,
            $command->expiryTimestamp,
        );
    }
}
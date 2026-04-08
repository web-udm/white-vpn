<?php

declare(strict_types=1);

namespace App\VPN\Port;

use App\VPN\Domain\Entity\VpnConnection;

final readonly class GetVpnConnectionURLQuery
{
    public const string PROTOCOL_VLESS = VpnConnection::PROTOCOL_VLESS;

    public function __construct(
        public int $telegramId,
        public string $protocol,
    ) {
    }
}

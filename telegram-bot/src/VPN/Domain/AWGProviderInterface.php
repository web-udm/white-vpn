<?php

declare(strict_types=1);

namespace App\VPN\Domain;

interface AWGProviderInterface
{
    public function createPeer(string $name): string;

    public function getPeerConfig(string $peerId): string;

    public function generateQrPngFromConfig(string $configContent): string;

    public function deletePeer(string $peerId): void;

    /** @return array<array{id: string, name: string}> */
    public function listPeers(): array;
}

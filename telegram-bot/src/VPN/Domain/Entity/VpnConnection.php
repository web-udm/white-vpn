<?php

declare(strict_types=1);

namespace App\VPN\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vpn_connection')]
class VpnConnection
{
    public const string PROTOCOL_VLESS = 'vless';
    public const string PROTOCOL_WG = 'wg';
    public const string PROTOCOL_HYSTERIA2 = 'hysteria2';

    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_REVOKED = 'revoked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int|null $id = null;

    #[ORM\Column]
    private int $userId;

    #[ORM\Column]
    private int $subscriptionId;

    #[ORM\Column(length: 20)]
    private string $protocol;

    #[ORM\Column(length: 36)]
    private string $externalId;

    #[ORM\Column]
    private int $maxDevices;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $userId,
        int $subscriptionId,
        string $protocol,
        string $externalId,
        int $maxDevices,
    ) {
        $this->userId = $userId;
        $this->subscriptionId = $subscriptionId;
        $this->protocol = $protocol;
        $this->externalId = $externalId;
        $this->maxDevices = $maxDevices;
        $this->status = self::STATUS_ACTIVE;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getMaxDevices(): int
    {
        return $this->maxDevices;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revoke(): void
    {
        $this->status = self::STATUS_REVOKED;
    }
}

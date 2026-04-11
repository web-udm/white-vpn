<?php

declare(strict_types=1);

namespace App\VPN\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vpn_connection')]
class VpnConnection
{
    public const string TYPE_SUBSCRIPTION = 'subscription';
    public const string TYPE_WIREGUARD = 'wireguard';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int|null $id = null;

    #[ORM\Column]
    private int $subscriptionId;

    #[ORM\Column(length: 20)]
    private string $type;

    #[ORM\Column(length: 36)]
    private string $externalId;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $subscriptionId,
        string $type,
        string $externalId,
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->type = $type;
        $this->externalId = $externalId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

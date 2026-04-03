<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vpn_connections')]
class VPNConnection
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_DISABLED = 'disabled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Subscription $subscription;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $subId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Subscription $subscription, string $subId)
    {
        $this->subscription = $subscription;
        $this->subId = $subId;
        $this->status = self::STATUS_ACTIVE;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    public function getSubId(): string
    {
        return $this->subId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function disable(): void
    {
        $this->status = self::STATUS_DISABLED;
    }
}

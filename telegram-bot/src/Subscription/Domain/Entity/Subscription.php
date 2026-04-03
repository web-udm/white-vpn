<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
class Subscription
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_PAUSED = 'paused';
    public const string STATUS_EXPIRED = 'expired';

    private const int REGULAR_QUOTA = 1;
    private const int VIP_QUOTA = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'boolean')]
    private bool $isVip;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $userId, bool $isVip = false, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->userId = $userId;
        $this->isVip = $isVip;
        $this->status = self::STATUS_ACTIVE;
        $this->expiresAt = $expiresAt;
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

    public function isVip(): bool
    {
        return $this->isVip;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getQuota(): int
    {
        return $this->isVip ? self::VIP_QUOTA : self::REGULAR_QUOTA;
    }

    public function expire(): void
    {
        $this->status = self::STATUS_EXPIRED;
    }

    public function pause(): void
    {
        $this->status = self::STATUS_PAUSED;
    }
}

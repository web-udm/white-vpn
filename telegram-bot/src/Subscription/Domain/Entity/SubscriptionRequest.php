<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Entity]
#[ORM\Table(name: 'subscription_requests')]
class SubscriptionRequest
{
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_APPROVED = 'approved';
    public const string STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'bigint')]
    private int $telegramId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(int $telegramId)
    {
        Assert::greaterThan($telegramId, 0);

        $this->telegramId = $telegramId;
        $this->status = self::STATUS_PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve(): void
    {
        Assert::same($this->status, self::STATUS_PENDING, 'Can only approve pending requests.');

        $this->status = self::STATUS_APPROVED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function reject(): void
    {
        Assert::same($this->status, self::STATUS_PENDING, 'Can only reject pending requests.');

        $this->status = self::STATUS_REJECTED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

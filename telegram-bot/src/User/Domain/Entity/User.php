<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\ValueObject\TelegramId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $telegramId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(TelegramId $telegramId)
    {
        $this->telegramId = $telegramId->value;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): TelegramId
    {
        return new TelegramId($this->telegramId);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

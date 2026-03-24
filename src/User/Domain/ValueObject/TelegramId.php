<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class TelegramId
{
    public int $value;

    public function __construct(int $value)
    {
        Assert::greaterThan($value, 0, 'Telegram ID must be positive, got %s.');

        $this->value = $value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

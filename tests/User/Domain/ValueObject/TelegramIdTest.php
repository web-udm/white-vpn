<?php

declare(strict_types=1);

namespace App\Tests\User\Domain\ValueObject;

use App\User\Domain\ValueObject\TelegramId;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

final class TelegramIdTest extends TestCase
{
    public function testCreatesWithValidId(): void
    {
        // Act
        $id = new TelegramId(123456789);

        // Assert
        $this->assertSame(123456789, $id->value);
    }

    public function testRejectsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new TelegramId(0);
    }

    public function testRejectsNegative(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new TelegramId(-1);
    }

    public function testEquals(): void
    {
        // Arrange
        $a = new TelegramId(123);
        $b = new TelegramId(123);
        $c = new TelegramId(456);

        // Act & Assert
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
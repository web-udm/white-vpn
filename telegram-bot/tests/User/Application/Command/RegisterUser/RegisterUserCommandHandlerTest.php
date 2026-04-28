<?php

declare(strict_types=1);

namespace App\Tests\User\Application\Command\RegisterUser;

use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Port\RegisterUserCommand;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterUserCommandHandlerTest extends KernelTestCase
{
    private RegisterUserCommandHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = new DoctrineUserRepository($entityManager);
        $this->handler = new RegisterUserCommandHandler($repository);
    }

    public function testRegistersNewUser(): void
    {
        // Act
        $user = ($this->handler)(new RegisterUserCommand(123456789));

        // Assert
        $this->assertSame(123456789, $user->getTelegramId()->value);
        $this->assertNotNull($user->getId());
    }

    public function testReturnsExistingUserOnDuplicate(): void
    {
        // Arrange
        $first = ($this->handler)(new RegisterUserCommand(123456789));

        // Act
        $second = ($this->handler)(new RegisterUserCommand(123456789));

        // Assert
        $this->assertSame($first->getId(), $second->getId());
    }
}
<?php

declare(strict_types=1);

namespace App\Tests\User\Application\Query\GetUserByTelegramId;

use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Application\Query\GetUserByTelegramId\GetUserByTelegramIdQueryHandler;
use App\User\Port\GetUserByTelegramIDQuery;
use App\User\Port\RegisterUserCommand;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetUserByTelegramIdQueryHandlerTest extends KernelTestCase
{
    private DoctrineUserRepository $repository;
    private GetUserByTelegramIdQueryHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineUserRepository($entityManager);
        $this->handler = new GetUserByTelegramIdQueryHandler($this->repository);
    }

    public function testReturnsNullWhenNotFound(): void
    {
        // Act
        $result = ($this->handler)(new GetUserByTelegramIDQuery(999999999));

        // Assert
        $this->assertNull($result);
    }

    public function testReturnsUserWhenExists(): void
    {
        // Arrange
        $registerHandler = new RegisterUserCommandHandler($this->repository);
        ($registerHandler)(new RegisterUserCommand(123456789));

        // Act
        $result = ($this->handler)(new GetUserByTelegramIDQuery(123456789));

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(123456789, $result->getTelegramId()->value);
    }
}
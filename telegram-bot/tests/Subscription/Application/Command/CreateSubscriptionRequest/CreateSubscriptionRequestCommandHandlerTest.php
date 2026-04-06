<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\CreateSubscriptionRequest;

use App\Subscription\Application\Command\CreateSubscriptionRequest\CreateSubscriptionRequestCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\CreateSubscriptionRequestCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateSubscriptionRequestCommandHandlerTest extends KernelTestCase
{
    private CreateSubscriptionRequestCommandHandler $handler;
    private RegisterUserCommandHandler $registerUserHandler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->handler = new CreateSubscriptionRequestCommandHandler($requestRepository);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
    }

    public function testCreatesRequest(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));

        // Act
        $request = ($this->handler)(new CreateSubscriptionRequestCommand(111222333));

        // Assert
        $this->assertNotNull($request->getId());
        $this->assertSame(111222333, $request->getTelegramId());
        $this->assertTrue($request->isPending());
    }
}

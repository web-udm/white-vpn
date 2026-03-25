<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Query\HasActiveSubscription;

use App\Subscription\Application\Query\HasActiveSubscription\HasActiveSubscriptionQueryHandler;
use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Port\GetSubscriptionStatusQuery;
use App\VPN\Port\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class HasActiveSubscriptionQueryHandlerTest extends KernelTestCase
{
    private HasActiveSubscriptionQueryHandler $handler;
    private RegisterUserCommandHandler $registerUserHandler;

    /** @var \Closure(GetSubscriptionStatusQuery): ?Subscription */
    private \Closure $getStatusStub;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);

        $this->getStatusStub = static fn(GetSubscriptionStatusQuery $q): ?Subscription => null;

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                GetSubscriptionStatusQuery::class => [
                    fn(GetSubscriptionStatusQuery $q) => ($this->getStatusStub)($q),
                ],
            ])),
        ]);

        $this->handler = new HasActiveSubscriptionQueryHandler($bus, $userRepository);
    }

    public function testReturnsFalseWhenUserNotFound(): void
    {
        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(999888777));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsFalseWhenNoClient(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $this->getStatusStub = static fn(GetSubscriptionStatusQuery $q): ?Subscription => null;

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsTrueWhenActive(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $this->getStatusStub = static fn(GetSubscriptionStatusQuery $q): Subscription => new Subscription(
            enabled: true,
            expiryTime: (time() + 86400) * 1000,
            uploadBytes: 0,
            downloadBytes: 0,
        );

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertTrue($result);
    }

    public function testReturnsFalseWhenExpired(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $this->getStatusStub = static fn(GetSubscriptionStatusQuery $q): Subscription => new Subscription(
            enabled: true,
            expiryTime: (time() - 86400) * 1000,
            uploadBytes: 0,
            downloadBytes: 0,
        );

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsFalseWhenProviderThrows(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $this->getStatusStub = static function (GetSubscriptionStatusQuery $q): never {
            throw new \RuntimeException('API down');
        };

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }
}
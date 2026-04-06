<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Query\HasActiveSubscription;

use App\Subscription\Application\Query\HasActiveSubscription\HasActiveSubscriptionQueryHandler;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\HasActiveSubscriptionQuery;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HasActiveSubscriptionQueryHandlerTest extends KernelTestCase
{
    private HasActiveSubscriptionQueryHandler $handler;
    private RegisterUserCommandHandler $registerUserHandler;
    private DoctrineSubscriptionRepository $subscriptionRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);

        $this->handler = new HasActiveSubscriptionQueryHandler(
            $this->subscriptionRepository,
            $userRepository,
        );
    }

    public function testReturnsFalseWhenUserNotFound(): void
    {
        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(999888777));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsFalseWhenNoSubscription(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsTrueWhenActiveSubscriptionExists(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = new Subscription($user->getId(), new \DateTimeImmutable('+30 days'));
        $this->subscriptionRepository->save($subscription);

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertTrue($result);
    }

    public function testReturnsFalseWhenSubscriptionExpired(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = new Subscription($user->getId(), new \DateTimeImmutable('+30 days'));
        $subscription->expire();
        $this->subscriptionRepository->save($subscription);

        // Act
        $result = ($this->handler)(new HasActiveSubscriptionQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }
}

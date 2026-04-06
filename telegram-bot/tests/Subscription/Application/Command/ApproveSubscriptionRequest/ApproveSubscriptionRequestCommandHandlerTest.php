<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\ApproveSubscriptionRequest;

use App\Subscription\Application\Command\ApproveSubscriptionRequest\ApproveSubscriptionRequestCommandHandler;
use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\ApproveSubscriptionRequestCommand;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\Subscription\Port\SubscriptionRequestException;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Port\CreateVpnConnectionCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class ApproveSubscriptionRequestCommandHandlerTest extends KernelTestCase
{
    private ApproveSubscriptionRequestCommandHandler $handler;
    private DoctrineSubscriptionRequestRepository $requestRepository;
    private DoctrineSubscriptionRepository $subscriptionRepository;
    private RegisterUserCommandHandler $registerUserHandler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $this->subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);

        $createSubscriptionHandler = new CreateSubscriptionCommandHandler($this->subscriptionRepository);

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                CreateSubscriptionCommand::class => [$createSubscriptionHandler],
                CreateVpnConnectionCommand::class => [fn() => null],
            ])),
        ]);

        $this->handler = new ApproveSubscriptionRequestCommandHandler(
            $bus,
            $this->requestRepository,
            $userRepository,
        );
    }

    public function testApprovesRequestAndCreatesSubscription(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $request = new SubscriptionRequest(111222333);
        $this->requestRepository->save($request);

        // Act
        ($this->handler)(new ApproveSubscriptionRequestCommand($request->getId()));

        // Assert
        $this->assertSame(SubscriptionRequest::STATUS_APPROVED, $request->getStatus());
        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId());
        $this->assertNotNull($subscription);
        $this->assertNotNull($subscription->getExpiresAt());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        // Assert
        $this->expectException(SubscriptionRequestException::class);
        $this->expectExceptionMessage('Request not found');

        // Act
        ($this->handler)(new ApproveSubscriptionRequestCommand(9999));
    }

    public function testThrowsWhenRequestNotPending(): void
    {
        // Arrange
        $request = new SubscriptionRequest(111222333);
        $request->reject();
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(SubscriptionRequestException::class);
        $this->expectExceptionMessage('Request is not pending');

        // Act
        ($this->handler)(new ApproveSubscriptionRequestCommand($request->getId()));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        // Arrange
        $request = new SubscriptionRequest(999888777);
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(SubscriptionRequestException::class);
        $this->expectExceptionMessage('User not found');

        // Act
        ($this->handler)(new ApproveSubscriptionRequestCommand($request->getId()));
    }
}

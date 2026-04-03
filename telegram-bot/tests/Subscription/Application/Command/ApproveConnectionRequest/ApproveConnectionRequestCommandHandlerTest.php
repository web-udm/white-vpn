<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\ApproveConnectionRequest;

use App\Subscription\Application\Command\ApproveConnectionRequest\ApproveConnectionRequestCommandHandler;
use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\ApproveConnectionRequestCommand;
use App\Subscription\Port\ConnectionRequestException;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class ApproveConnectionRequestCommandHandlerTest extends KernelTestCase
{
    private ApproveConnectionRequestCommandHandler $handler;
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

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                CreateSubscriptionCommand::class => [
                    fn(CreateSubscriptionCommand $c) => (new \App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler($this->subscriptionRepository))($c),
                ],
            ])),
        ]);

        $this->handler = new ApproveConnectionRequestCommandHandler(
            $bus,
            $this->requestRepository,
            $userRepository,
        );
    }

    public function testApprovesRequest(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $request = new SubscriptionRequest(111222333);
        $this->requestRepository->save($request);

        // Act
        ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));

        // Assert
        $this->assertSame(SubscriptionRequest::STATUS_APPROVED, $request->getStatus());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('Request not found');

        // Act
        ($this->handler)(new ApproveConnectionRequestCommand(9999));
    }

    public function testThrowsWhenRequestNotPending(): void
    {
        // Arrange
        $request = new SubscriptionRequest(111222333);
        $request->reject();
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('Request is not pending');

        // Act
        ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        // Arrange
        $request = new SubscriptionRequest(999888777);
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('User not found');

        // Act
        ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));
    }
}

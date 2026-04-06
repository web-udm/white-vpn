<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\RejectSubscriptionRequest;

use App\Subscription\Application\Command\RejectSubscriptionRequest\RejectSubscriptionRequestCommandHandler;
use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\RejectSubscriptionRequestCommand;
use App\Subscription\Port\SubscriptionRequestException;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RejectSubscriptionRequestCommandHandlerTest extends KernelTestCase
{
    private RejectSubscriptionRequestCommandHandler $handler;
    private DoctrineSubscriptionRequestRepository $requestRepository;
    private RegisterUserCommandHandler $registerUserHandler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->handler = new RejectSubscriptionRequestCommandHandler($this->requestRepository);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
    }

    public function testRejectsRequest(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $request = new SubscriptionRequest(111222333);
        $this->requestRepository->save($request);

        // Act
        ($this->handler)(new RejectSubscriptionRequestCommand($request->getId()));

        // Assert
        $this->assertSame(SubscriptionRequest::STATUS_REJECTED, $request->getStatus());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        // Assert
        $this->expectException(SubscriptionRequestException::class);
        $this->expectExceptionMessage('Request not found');

        // Act
        ($this->handler)(new RejectSubscriptionRequestCommand(9999));
    }

    public function testThrowsWhenRequestNotPending(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $request = new SubscriptionRequest(111222333);
        $request->approve();
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(SubscriptionRequestException::class);
        $this->expectExceptionMessage('Request is not pending');

        // Act
        ($this->handler)(new RejectSubscriptionRequestCommand($request->getId()));
    }
}

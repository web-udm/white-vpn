<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\ApproveConnectionRequest;

use App\Subscription\Application\Command\ApproveConnectionRequest\ApproveConnectionRequestCommandHandler;
use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineConnectionRequestRepository;
use App\Subscription\Port\ApproveConnectionRequestCommand;
use App\Subscription\Port\ConnectionRequestException;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Port\CreateClientCommand;
use App\VPN\Port\GetSubscriptionURLQuery;
use App\VPN\Port\VPNException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class ApproveConnectionRequestCommandHandlerTest extends KernelTestCase
{
    private ApproveConnectionRequestCommandHandler $handler;
    private DoctrineConnectionRequestRepository $requestRepository;
    private RegisterUserCommandHandler $registerUserHandler;

    /** @var \Closure(CreateClientCommand): void */
    private \Closure $createClientStub;

    /** @var \Closure(GetSubscriptionURLQuery): string */
    private \Closure $getSubscriptionURLStub;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineConnectionRequestRepository($entityManager);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);

        $this->createClientStub = static function (CreateClientCommand $command): void {};
        $this->getSubscriptionURLStub = static fn(GetSubscriptionURLQuery $query): string => 'https://sub.example.com/sub/some-uuid';

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                CreateClientCommand::class => [
                    fn(CreateClientCommand $c) => ($this->createClientStub)($c),
                ],
                GetSubscriptionURLQuery::class => [
                    fn(GetSubscriptionURLQuery $q) => ($this->getSubscriptionURLStub)($q),
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
        $request = new ConnectionRequest(111222333);
        $this->requestRepository->save($request);

        // Act
        $subscriptionURL = ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));

        // Assert
        $this->assertSame('https://sub.example.com/sub/some-uuid', $subscriptionURL);
        $this->assertSame(ConnectionRequest::STATUS_APPROVED, $request->getStatus());
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
        $request = new ConnectionRequest(111222333);
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
        $request = new ConnectionRequest(999888777);
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('User not found');

        // Act
        ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));
    }

    public function testThrowsWhenVPNProviderFails(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $request = new ConnectionRequest(111222333);
        $this->requestRepository->save($request);
        $this->createClientStub = static function (CreateClientCommand $command): never {
            throw new VPNException('API down');
        };

        // Assert
        $this->expectException(HandlerFailedException::class);

        // Act
        try {
            ($this->handler)(new ApproveConnectionRequestCommand($request->getId()));
        } catch (HandlerFailedException $e) {
            $this->assertInstanceOf(VPNException::class, $e->getPrevious());
            $this->assertSame('API down', $e->getPrevious()->getMessage());
            throw $e;
        }
    }
}
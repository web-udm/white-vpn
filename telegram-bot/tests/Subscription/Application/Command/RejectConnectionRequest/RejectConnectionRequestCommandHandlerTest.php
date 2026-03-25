<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\RejectConnectionRequest;

use App\Subscription\Application\Command\RejectConnectionRequest\RejectConnectionRequestCommandHandler;
use App\Subscription\Port\ConnectionRequestException;
use App\Subscription\Port\RejectConnectionRequestCommand;
use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineConnectionRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RejectConnectionRequestCommandHandlerTest extends KernelTestCase
{
    private RejectConnectionRequestCommandHandler $handler;
    private DoctrineConnectionRequestRepository $requestRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineConnectionRequestRepository($entityManager);
        $this->handler = new RejectConnectionRequestCommandHandler($this->requestRepository);
    }

    public function testRejectsRequest(): void
    {
        // Arrange
        $request = new ConnectionRequest(111222333);
        $this->requestRepository->save($request);

        // Act
        ($this->handler)(new RejectConnectionRequestCommand($request->getId()));

        // Assert
        $this->assertSame(ConnectionRequest::STATUS_REJECTED, $request->getStatus());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('Request not found');

        // Act
        ($this->handler)(new RejectConnectionRequestCommand(9999));
    }

    public function testThrowsWhenRequestNotPending(): void
    {
        // Arrange
        $request = new ConnectionRequest(111222333);
        $request->approve();
        $this->requestRepository->save($request);

        // Assert
        $this->expectException(ConnectionRequestException::class);
        $this->expectExceptionMessage('Request is not pending');

        // Act
        ($this->handler)(new RejectConnectionRequestCommand($request->getId()));
    }
}

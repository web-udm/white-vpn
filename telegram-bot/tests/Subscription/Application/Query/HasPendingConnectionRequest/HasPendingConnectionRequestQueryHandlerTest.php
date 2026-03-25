<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Query\HasPendingConnectionRequest;

use App\Subscription\Application\Query\HasPendingConnectionRequest\HasPendingConnectionRequestQueryHandler;
use App\Subscription\Port\HasPendingConnectionRequestQuery;
use App\Subscription\Domain\Entity\ConnectionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineConnectionRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HasPendingConnectionRequestQueryHandlerTest extends KernelTestCase
{
    private HasPendingConnectionRequestQueryHandler $handler;
    private DoctrineConnectionRequestRepository $requestRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineConnectionRequestRepository($entityManager);
        $this->handler = new HasPendingConnectionRequestQueryHandler($this->requestRepository);
    }

    public function testReturnsFalseWhenNoPending(): void
    {
        // Act
        $result = ($this->handler)(new HasPendingConnectionRequestQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsTrueWhenPendingExists(): void
    {
        // Arrange
        $this->requestRepository->save(new ConnectionRequest(111222333));

        // Act
        $result = ($this->handler)(new HasPendingConnectionRequestQuery(111222333));

        // Assert
        $this->assertTrue($result);
    }
}

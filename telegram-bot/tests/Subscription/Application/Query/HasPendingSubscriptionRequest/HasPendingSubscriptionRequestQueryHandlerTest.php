<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Query\HasPendingSubscriptionRequest;

use App\Subscription\Application\Query\HasPendingSubscriptionRequest\HasPendingSubscriptionRequestQueryHandler;
use App\Subscription\Domain\Entity\SubscriptionRequest;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\HasPendingSubscriptionRequestQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class HasPendingSubscriptionRequestQueryHandlerTest extends KernelTestCase
{
    private HasPendingSubscriptionRequestQueryHandler $handler;
    private DoctrineSubscriptionRequestRepository $requestRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $this->handler = new HasPendingSubscriptionRequestQueryHandler($this->requestRepository);
    }

    public function testReturnsFalseWhenNoPending(): void
    {
        // Act
        $result = ($this->handler)(new HasPendingSubscriptionRequestQuery(111222333));

        // Assert
        $this->assertFalse($result);
    }

    public function testReturnsTrueWhenPendingExists(): void
    {
        // Arrange
        $this->requestRepository->save(new SubscriptionRequest(111222333));

        // Act
        $result = ($this->handler)(new HasPendingSubscriptionRequestQuery(111222333));

        // Assert
        $this->assertTrue($result);
    }
}

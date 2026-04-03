<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\CreateSubscriptionRequest;

use App\Subscription\Application\Command\CreateSubscriptionRequest\CreateSubscriptionRequestCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\CreateSubscriptionRequestCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateSubscriptionRequestCommandHandlerTest extends KernelTestCase
{
    private CreateSubscriptionRequestCommandHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $this->handler = new CreateSubscriptionRequestCommandHandler($requestRepository);
    }

    public function testCreatesRequest(): void
    {
        // Act
        $request = ($this->handler)(new CreateSubscriptionRequestCommand(111222333));

        // Assert
        $this->assertNotNull($request->getId());
        $this->assertSame(111222333, $request->getTelegramId());
        $this->assertTrue($request->isPending());
    }
}

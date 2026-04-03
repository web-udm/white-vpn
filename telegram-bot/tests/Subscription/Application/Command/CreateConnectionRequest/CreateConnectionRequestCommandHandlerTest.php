<?php

declare(strict_types=1);

namespace App\Tests\Subscription\Application\Command\CreateConnectionRequest;

use App\Subscription\Application\Command\CreateConnectionRequest\CreateConnectionRequestCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRequestRepository;
use App\Subscription\Port\CreateConnectionRequestCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateConnectionRequestCommandHandlerTest extends KernelTestCase
{
    private CreateConnectionRequestCommandHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $requestRepository = new DoctrineSubscriptionRequestRepository($entityManager);
        $this->handler = new CreateConnectionRequestCommandHandler($requestRepository);
    }

    public function testCreatesRequest(): void
    {
        // Act
        $request = ($this->handler)(new CreateConnectionRequestCommand(111222333));

        // Assert
        $this->assertNotNull($request->getId());
        $this->assertSame(111222333, $request->getTelegramId());
        $this->assertTrue($request->isPending());
    }
}

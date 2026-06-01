<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Query\GetVpnConnectionURLs;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Application\Query\GetActiveSubscription\GetActiveSubscriptionQueryHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Query\GetVpnConnectionURLs\GetVpnConnectionURLsQueryHandler;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\GetVpnConnectionURLsQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class GetVpnConnectionURLsQueryHandlerTest extends KernelTestCase
{
    private GetVpnConnectionURLsQueryHandler $handler;
    private DoctrineVpnConnectionRepository $vpnConnectionRepository;
    private RegisterUserCommandHandler $registerUserHandler;
    private CreateSubscriptionCommandHandler $createSubscriptionHandler;
    private MockObject&VPNProviderInterface $vpnProvider;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $this->vpnConnectionRepository = new DoctrineVpnConnectionRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
        $this->createSubscriptionHandler = new CreateSubscriptionCommandHandler($subscriptionRepository);
        $this->vpnProvider = $this->createMock(VPNProviderInterface::class);

        $getActiveSubscriptionHandler = new GetActiveSubscriptionQueryHandler($subscriptionRepository, $userRepository);

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                GetActiveSubscriptionQuery::class => [$getActiveSubscriptionHandler],
            ])),
        ]);

        $this->handler = new GetVpnConnectionURLsQueryHandler(
            $bus,
            $this->vpnProvider,
            $this->vpnConnectionRepository,
        );
    }

    public function testReturnsVpnURLsForActiveSubscription(): void
    {
        // Arrange
        $subId = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, $subId);
        $this->vpnConnectionRepository->save($connection);

        $this->vpnProvider
            ->expects($this->once())
            ->method('getConnectionURL')
            ->with($subId)
            ->willReturn('https://vpn.example.com/sub/' . $subId);

        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert
        $this->assertCount(1, $urls);
        $this->assertSame('https://vpn.example.com/sub/' . $subId, $urls[0]);
    }

    public function testReturnsAllVpnURLsForMultipleConnections(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $this->vpnConnectionRepository->save(new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'subid-server1'));
        $this->vpnConnectionRepository->save(new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'subid-server2'));

        $this->vpnProvider
            ->expects($this->exactly(2))
            ->method('getConnectionURL')
            ->willReturnCallback(fn (string $subId) => 'https://vpn.example.com/sub/' . $subId);

        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert
        $this->assertCount(2, $urls);
    }

    public function testReturnsEmptyArrayWhenUserNotFound(): void
    {
        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(999888777));

        // Assert
        $this->assertSame([], $urls);
    }

    public function testReturnsEmptyArrayWhenNoActiveSubscription(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));

        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert
        $this->assertSame([], $urls);
    }
}

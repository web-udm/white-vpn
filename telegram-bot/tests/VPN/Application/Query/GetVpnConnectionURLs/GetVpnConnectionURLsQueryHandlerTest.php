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
use App\VPN\Application\Command\CreateMtProxyConnection\CreateMtProxyConnectionCommandHandler;
use App\VPN\Application\Query\GetVpnConnectionURLs\GetVpnConnectionURLsQueryHandler;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\CreateMtProxyConnectionCommand;
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
        $createMtProxyHandler = new CreateMtProxyConnectionCommandHandler($this->vpnConnectionRepository);

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                GetActiveSubscriptionQuery::class => [$getActiveSubscriptionHandler],
                CreateMtProxyConnectionCommand::class => [$createMtProxyHandler],
            ])),
        ]);

        $this->handler = new GetVpnConnectionURLsQueryHandler(
            $bus,
            $this->vpnProvider,
            $this->vpnConnectionRepository,
            'whitevpn.tech',
            8443,
        );
    }

    public function testReturnsVpnAndMtProxyURLs(): void
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

        // Assert — VPN + MTProxy (created lazily)
        $this->assertCount(2, $urls);
        $this->assertSame('https://vpn.example.com/sub/' . $subId, $urls[0]);
        $this->assertStringStartsWith('https://t.me/proxy?', $urls[1]);
    }

    public function testReturnsAllVpnURLsPlusMtProxy(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection1 = new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'subid-server1');
        $connection2 = new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'subid-server2');
        $this->vpnConnectionRepository->save($connection1);
        $this->vpnConnectionRepository->save($connection2);

        $this->vpnProvider
            ->expects($this->exactly(2))
            ->method('getConnectionURL')
            ->willReturnCallback(fn (string $subId) => 'https://vpn.example.com/sub/' . $subId);

        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert — 2 VPN + 1 MTProxy
        $this->assertCount(3, $urls);
    }

    public function testCreatesMtProxyLazilyWhenMissing(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'aaaaaaaa-bbbb-4ccc-8ddd-ffffffffffff');
        $this->vpnConnectionRepository->save($connection);

        $this->vpnProvider->method('getConnectionURL')->willReturn('https://vpn.example.com');

        // Act
        ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert — MTProxy connection now exists in DB
        $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription->getId());
        $types = array_map(fn ($c) => $c->getType(), $connections);
        $this->assertContains(VpnConnection::TYPE_MTPROXY, $types);
    }

    public function testDoesNotDuplicateMtProxyOnRepeatCall(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_SUBSCRIPTION, 'aaaaaaaa-bbbb-4ccc-8ddd-111111111111');
        $this->vpnConnectionRepository->save($connection);

        $this->vpnProvider->method('getConnectionURL')->willReturn('https://vpn.example.com');

        // Act — два вызова подряд
        ($this->handler)(new GetVpnConnectionURLsQuery(111222333));
        ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert — MTProxy создан ровно один раз
        $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription->getId());
        $mtproxyCount = count(array_filter($connections, fn ($c) => $c->getType() === VpnConnection::TYPE_MTPROXY));
        $this->assertSame(1, $mtproxyCount);
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

    public function testReturnsMtProxyURLWithCorrectFormat(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $secret = 'aabbccdd11223344aabbccdd11223344';
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_MTPROXY, $secret);
        $this->vpnConnectionRepository->save($connection);

        $this->vpnProvider->expects($this->never())->method('getConnectionURL');

        // Act
        $urls = ($this->handler)(new GetVpnConnectionURLsQuery(111222333));

        // Assert
        $this->assertCount(1, $urls);
        $this->assertSame("https://t.me/proxy?server=whitevpn.tech&port=8443&secret=dd{$secret}", $urls[0]);
    }
}

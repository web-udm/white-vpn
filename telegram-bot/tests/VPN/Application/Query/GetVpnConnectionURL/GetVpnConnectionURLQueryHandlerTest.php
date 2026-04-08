<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Query\GetVpnConnectionURL;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Query\GetVpnConnectionURL\GetVpnConnectionURLQueryHandler;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\GetVpnConnectionURLQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetVpnConnectionURLQueryHandlerTest extends KernelTestCase
{
    private GetVpnConnectionURLQueryHandler $handler;
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

        $this->handler = new GetVpnConnectionURLQueryHandler(
            $this->vpnProvider,
            $this->vpnConnectionRepository,
            $userRepository,
        );
    }

    public function testReturnsURLWhenConnectionExists(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection = new VpnConnection($subscription->getId(), VpnConnection::PROTOCOL_VLESS, $user->getSubId(), 2);
        $this->vpnConnectionRepository->save($connection);

        $this->vpnProvider
            ->expects($this->once())
            ->method('getConnectionURL')
            ->with($user->getSubId())
            ->willReturn('vless://abc123@vpn.example.com:443');

        // Act
        $url = ($this->handler)(new GetVpnConnectionURLQuery(111222333, GetVpnConnectionURLQuery::PROTOCOL_VLESS));

        // Assert
        $this->assertSame('vless://abc123@vpn.example.com:443', $url);
    }

    public function testReturnsNullWhenUserNotFound(): void
    {
        // Act
        $url = ($this->handler)(new GetVpnConnectionURLQuery(999888777, GetVpnConnectionURLQuery::PROTOCOL_VLESS));

        // Assert
        $this->assertNull($url);
    }

    public function testReturnsNullWhenNoActiveConnection(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(111222333));

        // Act
        $url = ($this->handler)(new GetVpnConnectionURLQuery(111222333, GetVpnConnectionURLQuery::PROTOCOL_VLESS));

        // Assert
        $this->assertNull($url);
    }

    public function testReturnsNullWhenConnectionRevoked(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $connection = new VpnConnection($subscription->getId(), VpnConnection::PROTOCOL_VLESS, $user->getSubId(), 2);
        $connection->revoke();
        $this->vpnConnectionRepository->save($connection);

        // Act
        $url = ($this->handler)(new GetVpnConnectionURLQuery(111222333, GetVpnConnectionURLQuery::PROTOCOL_VLESS));

        // Assert
        $this->assertNull($url);
    }
}

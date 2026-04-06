<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Command\CreateVpnConnection;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Command\CreateVpnConnection\CreateVpnConnectionCommandHandler;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\CreateVpnConnectionCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateVpnConnectionCommandHandlerTest extends KernelTestCase
{
    private CreateVpnConnectionCommandHandler $handler;
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

        $this->handler = new CreateVpnConnectionCommandHandler(
            $this->vpnProvider,
            $this->vpnConnectionRepository,
        );
    }

    public function testCreatesVpnConnectionAndCallsProvider(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $expiresAt = new \DateTimeImmutable('+30 days');
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), $expiresAt));

        $this->vpnProvider
            ->expects($this->once())
            ->method('createClient')
            ->with($user->getSubId(), 1, $expiresAt->getTimestamp() * 1000);

        // Act
        ($this->handler)(new CreateVpnConnectionCommand(
            subscriptionId: $subscription->getId(),
            subId: $user->getSubId(),
            protocol: VpnConnection::PROTOCOL_VLESS,
            maxDevices: 1,
            expiresAt: $expiresAt,
        ));

        // Assert
        $connection = $this->vpnConnectionRepository->findActiveByUserIdAndProtocol(
            $user->getId(),
            VpnConnection::PROTOCOL_VLESS,
        );

        $this->assertNotNull($connection);
        $this->assertSame(VpnConnection::STATUS_ACTIVE, $connection->getStatus());
        $this->assertSame(1, $connection->getMaxDevices());
        $this->assertSame($user->getSubId(), $connection->getExternalId());
    }

    public function testVipUserGetsHigherMaxDevices(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(444555666));
        $expiresAt = new \DateTimeImmutable('+30 days');
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), $expiresAt, true));

        $this->vpnProvider
            ->expects($this->once())
            ->method('createClient')
            ->with($user->getSubId(), 10, $expiresAt->getTimestamp() * 1000);

        // Act
        ($this->handler)(new CreateVpnConnectionCommand(
            subscriptionId: $subscription->getId(),
            subId: $user->getSubId(),
            protocol: VpnConnection::PROTOCOL_VLESS,
            maxDevices: 10,
            expiresAt: $expiresAt,
        ));

        // Assert
        $connection = $this->vpnConnectionRepository->findActiveByUserIdAndProtocol(
            $user->getId(),
            VpnConnection::PROTOCOL_VLESS,
        );

        $this->assertNotNull($connection);
        $this->assertSame(10, $connection->getMaxDevices());
    }
}

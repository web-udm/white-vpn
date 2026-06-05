<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Command\CreateAWGConnections;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Command\CreateAWGConnections\CreateAWGConnectionsCommandHandler;
use App\VPN\Domain\AWGProviderInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\CreateAWGConnectionsCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateAWGConnectionsCommandHandlerTest extends KernelTestCase
{
    private CreateAWGConnectionsCommandHandler $handler;
    private DoctrineVpnConnectionRepository $vpnConnectionRepository;
    private RegisterUserCommandHandler $registerUserHandler;
    private CreateSubscriptionCommandHandler $createSubscriptionHandler;
    private MockObject&AWGProviderInterface $awgProvider;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $this->vpnConnectionRepository = new DoctrineVpnConnectionRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
        $this->createSubscriptionHandler = new CreateSubscriptionCommandHandler($subscriptionRepository);
        $this->awgProvider = $this->createMock(AWGProviderInterface::class);

        $this->handler = new CreateAWGConnectionsCommandHandler(
            $this->awgProvider,
            $this->vpnConnectionRepository,
            new NullLogger(),
        );
    }

    public function testCreatesThreePeers(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(100200300));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $subscriptionId = $subscription->getId();

        $this->awgProvider
            ->expects($this->exactly(3))
            ->method('createPeer')
            ->willReturnOnConsecutiveCalls('peer-uuid-1', 'peer-uuid-2', 'peer-uuid-3');

        // Act
        ($this->handler)(new CreateAWGConnectionsCommand($subscriptionId));

        // Assert
        $connections = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $subscriptionId,
        );
        $this->assertCount(3, $connections);
        foreach ($connections as $connection) {
            $this->assertSame(VpnConnection::TYPE_AMNEZIA_WIREGUARD, $connection->getType());
            $this->assertSame($subscriptionId, $connection->getSubscriptionId());
        }
    }

    public function testSkipsIfAnyPeerAlreadyExists(): void
    {
        // Arrange: 1 existing peer
        $user = ($this->registerUserHandler)(new RegisterUserCommand(300400500));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $subscriptionId = $subscription->getId();

        $this->vpnConnectionRepository->save(
            new VpnConnection($subscriptionId, VpnConnection::TYPE_AMNEZIA_WIREGUARD, 'existing-peer-id'),
        );

        // Act
        $this->awgProvider->expects($this->never())->method('createPeer');
        ($this->handler)(new CreateAWGConnectionsCommand($subscriptionId));

        // Assert: still only 1
        $connections = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $subscriptionId,
        );
        $this->assertCount(1, $connections);
    }

    public function testSkipsIfAllPeersAlreadyExist(): void
    {
        // Arrange: full set already provisioned
        $user = ($this->registerUserHandler)(new RegisterUserCommand(600700800));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $subscriptionId = $subscription->getId();

        $this->awgProvider->method('createPeer')->willReturn('some-peer-id');
        ($this->handler)(new CreateAWGConnectionsCommand($subscriptionId));

        // Act: run again
        $this->awgProvider->expects($this->never())->method('createPeer');
        ($this->handler)(new CreateAWGConnectionsCommand($subscriptionId));

        // Assert: still 3
        $connections = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $subscriptionId,
        );
        $this->assertCount(3, $connections);
    }
}

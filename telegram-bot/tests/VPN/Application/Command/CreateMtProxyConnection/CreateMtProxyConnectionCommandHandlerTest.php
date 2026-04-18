<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Command\CreateMtProxyConnection;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Command\CreateMtProxyConnection\CreateMtProxyConnectionCommandHandler;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\CreateMtProxyConnectionCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateMtProxyConnectionCommandHandlerTest extends KernelTestCase
{
    private CreateMtProxyConnectionCommandHandler $handler;
    private DoctrineVpnConnectionRepository $vpnConnectionRepository;
    private RegisterUserCommandHandler $registerUserHandler;
    private CreateSubscriptionCommandHandler $createSubscriptionHandler;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $this->vpnConnectionRepository = new DoctrineVpnConnectionRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
        $this->createSubscriptionHandler = new CreateSubscriptionCommandHandler($subscriptionRepository);
        $this->handler = new CreateMtProxyConnectionCommandHandler($this->vpnConnectionRepository);
    }

    public function testCreatesConnectionWithMtProxyType(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));

        // Act
        ($this->handler)(new CreateMtProxyConnectionCommand(subscriptionId: $subscription->getId()));

        // Assert
        $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription->getId());
        $this->assertCount(1, $connections);
        $this->assertSame(VpnConnection::TYPE_MTPROXY, $connections[0]->getType());
    }

    public function testSecretIs32HexChars(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));

        // Act
        ($this->handler)(new CreateMtProxyConnectionCommand(subscriptionId: $subscription->getId()));

        // Assert
        $connections = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription->getId());
        $secret = $connections[0]->getExternalId();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $secret);
    }

    public function testSecretsAreUniqueAcrossConnections(): void
    {
        // Arrange
        $user1 = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $user2 = ($this->registerUserHandler)(new RegisterUserCommand(444555666));
        $subscription1 = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user1->getId(), new \DateTimeImmutable('+30 days')));
        $subscription2 = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user2->getId(), new \DateTimeImmutable('+30 days')));

        // Act
        ($this->handler)(new CreateMtProxyConnectionCommand(subscriptionId: $subscription1->getId()));
        ($this->handler)(new CreateMtProxyConnectionCommand(subscriptionId: $subscription2->getId()));

        // Assert
        $connections1 = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription1->getId());
        $connections2 = $this->vpnConnectionRepository->findAllActiveBySubscriptionId($subscription2->getId());
        $this->assertNotSame($connections1[0]->getExternalId(), $connections2[0]->getExternalId());
    }
}

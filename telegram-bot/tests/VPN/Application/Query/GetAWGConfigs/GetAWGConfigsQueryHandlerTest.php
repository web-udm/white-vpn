<?php

declare(strict_types=1);

namespace App\Tests\VPN\Application\Query\GetAWGConfigs;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Application\Query\GetActiveSubscription\GetActiveSubscriptionQueryHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Application\Query\GetAWGConfigs\GetAWGConfigsQueryHandler;
use App\VPN\Domain\AWGProviderInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use App\VPN\Port\AWGPeerConfig;
use App\VPN\Port\GetAWGConfigsQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class GetAWGConfigsQueryHandlerTest extends KernelTestCase
{
    private GetAWGConfigsQueryHandler $handler;
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

        $getActiveSubscriptionHandler = new GetActiveSubscriptionQueryHandler($subscriptionRepository, $userRepository);

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                GetActiveSubscriptionQuery::class => [$getActiveSubscriptionHandler],
            ])),
        ]);

        $this->handler = new GetAWGConfigsQueryHandler(
            $bus,
            $this->awgProvider,
            $this->vpnConnectionRepository,
        );
    }

    public function testReturnsEmptyForNoActiveSubscription(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111333555));

        // Act
        $result = ($this->handler)(new GetAWGConfigsQuery($user->getTelegramId()->value));

        // Assert
        $this->assertSame([], $result);
    }

    public function testReturnsConfigsForEachWireguardConnection(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(222444666));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $subscriptionId = $subscription->getId();

        foreach (['peer-id-1', 'peer-id-2', 'peer-id-3'] as $peerId) {
            $this->vpnConnectionRepository->save(
                new VpnConnection($subscriptionId, VpnConnection::TYPE_AMNEZIA_WIREGUARD, $peerId),
            );
        }

        $this->awgProvider
            ->method('getPeerConfig')
            ->willReturnCallback(fn (string $id) => "[Interface]\n# {$id}");
        $this->awgProvider
            ->method('generateQrPngFromConfig')
            ->willReturn('fake-png-bytes');

        // Act
        $result = ($this->handler)(new GetAWGConfigsQuery($user->getTelegramId()->value));

        // Assert
        $this->assertCount(3, $result);
        foreach ($result as $index => $config) {
            $this->assertInstanceOf(AWGPeerConfig::class, $config);
            $this->assertSame(sprintf('awg-%d.conf', $index + 1), $config->filename);
            $this->assertStringContainsString('[Interface]', $config->configContent);
            $this->assertSame('fake-png-bytes', $config->qrCodePng);
        }
    }

    public function testReturnsEmptyWhenNoWireguardConnections(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(333555777));
        ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));

        // Act
        $result = ($this->handler)(new GetAWGConfigsQuery($user->getTelegramId()->value));

        // Assert
        $this->assertSame([], $result);
    }
}

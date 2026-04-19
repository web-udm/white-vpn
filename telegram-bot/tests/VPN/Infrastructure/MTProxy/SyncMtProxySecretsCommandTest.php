<?php

declare(strict_types=1);

namespace App\Tests\VPN\Infrastructure\MTProxy;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use App\Subscription\Infrastructure\Persistence\DoctrineSubscriptionRepository;
use App\Subscription\Port\CreateSubscriptionCommand;
use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use App\User\Port\RegisterUserCommand;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Infrastructure\MTProxy\SyncMtProxySecretsCommand;
use App\VPN\Infrastructure\Persistence\DoctrineVpnConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncMtProxySecretsCommandTest extends KernelTestCase
{
    private SyncMtProxySecretsCommand $command;
    private DoctrineVpnConnectionRepository $vpnConnectionRepository;
    private RegisterUserCommandHandler $registerUserHandler;
    private CreateSubscriptionCommandHandler $createSubscriptionHandler;
    private DoctrineSubscriptionRepository $subscriptionRepository;
    private string $secretsFile;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepository = new DoctrineUserRepository($entityManager);
        $this->subscriptionRepository = new DoctrineSubscriptionRepository($entityManager);
        $this->vpnConnectionRepository = new DoctrineVpnConnectionRepository($entityManager);
        $this->registerUserHandler = new RegisterUserCommandHandler($userRepository);
        $this->createSubscriptionHandler = new CreateSubscriptionCommandHandler($this->subscriptionRepository);

        $this->secretsFile = sys_get_temp_dir() . '/mtproxy_test_secrets_' . uniqid() . '.txt';
        $this->command = new SyncMtProxySecretsCommand($this->vpnConnectionRepository, $this->secretsFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->secretsFile)) {
            unlink($this->secretsFile);
        }
        parent::tearDown();
    }

    public function testWritesActiveSecretsToFile(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $secret = 'aabbccdd11223344aabbccdd11223344';
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_MTPROXY, $secret);
        $this->vpnConnectionRepository->save($connection);

        // Act
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        // Assert
        $this->assertSame(0, $tester->getStatusCode());
        $content = file_get_contents($this->secretsFile);
        $this->assertStringContainsString($secret, $content);
    }

    public function testExcludesExpiredSubscriptions(): void
    {
        // Arrange
        $user = ($this->registerUserHandler)(new RegisterUserCommand(111222333));
        $subscription = ($this->createSubscriptionHandler)(new CreateSubscriptionCommand($user->getId(), new \DateTimeImmutable('+30 days')));
        $subscription->expire();
        $this->subscriptionRepository->save($subscription);

        $secret = 'aabbccdd11223344aabbccdd11223344';
        $connection = new VpnConnection($subscription->getId(), VpnConnection::TYPE_MTPROXY, $secret);
        $this->vpnConnectionRepository->save($connection);

        // Act
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        // Assert
        $content = file_get_contents($this->secretsFile);
        $this->assertStringNotContainsString($secret, $content);
    }
}

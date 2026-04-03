<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Command;

use App\Subscription\Domain\Entity\VPNConnection;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\Repository\VPNConnectionRepositoryInterface;
use App\VPN\Port\DisableClientCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:subscription:deactivate-expired',
    description: 'Deactivate expired subscriptions and disable their VPN connections in XUI',
)]
final class DeactivateExpiredSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly VPNConnectionRepositoryInterface $connectionRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptions = $this->subscriptionRepository->findExpiredActive();

        if (empty($subscriptions)) {
            $output->writeln('No expired subscriptions found.');
            return Command::SUCCESS;
        }

        foreach ($subscriptions as $subscription) {
            $connections = $this->connectionRepository->findBySubscriptionId($subscription->getId() ?? 0);

            foreach ($connections as $connection) {
                if (!$connection->isActive()) {
                    continue;
                }

                try {
                    $this->messageBus->dispatch(new DisableClientCommand($connection->getSubId()));
                    $connection->disable();
                    $this->connectionRepository->save($connection);
                    $output->writeln("Disabled connection {$connection->getSubId()}");
                } catch (\Throwable $e) {
                    $output->writeln("Failed to disable {$connection->getSubId()}: {$e->getMessage()}");
                }
            }

            $subscription->expire();
            $this->subscriptionRepository->save($subscription);
            $output->writeln("Expired subscription #{$subscription->getId()} (user #{$subscription->getUserId()})");
        }

        return Command::SUCCESS;
    }
}

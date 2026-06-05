<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Console;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\CreateAWGConnectionsCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:awg:provision-all',
    description: 'Create AWG peers and VpnConnection DB records for all active subscriptions',
)]
final class ProvisionAWGConnectionsCommand extends Command
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptions = $this->subscriptionRepository->findAllActive();
        $total         = count($subscriptions);
        $created       = 0;
        $skipped       = 0;
        $failed        = 0;

        $output->writeln("Found {$total} active subscriptions.");

        foreach ($subscriptions as $subscription) {
            $subscriptionId = $subscription->getId();
            if ($subscriptionId === null) {
                continue;
            }

            $existing = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
                VpnConnection::TYPE_AMNEZIA_WIREGUARD,
                $subscriptionId,
            );

            if (count($existing) >= 3) {
                $output->writeln("  [SKIP] #{$subscriptionId}: already has " . count($existing) . " peers");
                $skipped++;
                continue;
            }

            try {
                $this->messageBus->dispatch(new CreateAWGConnectionsCommand($subscriptionId));
                $output->writeln("  [OK]   #{$subscriptionId}: peers provisioned");
                $created++;
            } catch (\Throwable $e) {
                $output->writeln("  [ERR]  #{$subscriptionId}: {$e->getMessage()}");
                $failed++;
            }
        }

        $output->writeln("Done. Created: {$created}, skipped: {$skipped}, failed: {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

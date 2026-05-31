<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Console;

use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Domain\VPNProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'vpn:add-clients-to-inbound',
    description: 'Add all existing subscription clients to a specified 3x-ui inbound',
)]
final class AddClientsToInboundCommand extends Command
{
    public function __construct(
        private readonly VPNProviderInterface $vpnProvider,
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('inbound-id', InputArgument::REQUIRED, 'Target inbound ID in 3x-ui');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inboundId = (int) $input->getArgument('inbound-id');

        $connections = $this->vpnConnectionRepository->findAllByType(VpnConnection::TYPE_SUBSCRIPTION);
        $total = count($connections);

        $output->writeln(sprintf('Found %d subscription connection(s). Adding to inbound %d...', $total, $inboundId));

        $success = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($connections as $i => $connection) {
            $externalId = $connection->getExternalId();

            try {
                $this->vpnProvider->createClient($externalId, [$inboundId], limitIp: 3, expiryTimestamp: 0);
                $output->writeln(sprintf('[%d/%d] OK: %s', $i + 1, $total, $externalId));
                $success++;
            } catch (\Throwable $e) {
                $message = $e->getMessage();

                if (str_contains($message, 'already exists') || str_contains($message, 'Duplicate')) {
                    $output->writeln(sprintf('[%d/%d] SKIP (already exists): %s', $i + 1, $total, $externalId));
                    $skipped++;
                } else {
                    $output->writeln(sprintf('[%d/%d] ERROR: %s — %s', $i + 1, $total, $externalId, $message));
                    $errors++;
                }
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('Done. Success: %d | Skipped: %d | Errors: %d', $success, $skipped, $errors));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Console;

use App\VPN\Domain\VPNProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'vpn:test-create-client',
    description: 'Create a test client in 3x-ui to verify API connectivity',
)]
final class TestCreateClientCommand extends Command
{
    public function __construct(private readonly VPNProviderInterface $vpnProvider)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subId = 'test-' . bin2hex(random_bytes(8));
        $inboundIds = $this->vpnProvider->getInboundIds();

        $output->writeln('SubID:    ' . $subId);
        $output->writeln('Inbounds: ' . implode(', ', $inboundIds));
        $output->writeln('');

        try {
            $this->vpnProvider->createClient($subId, $inboundIds, 3, 0);
            $output->writeln('<info>OK</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>FAIL</error> — ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

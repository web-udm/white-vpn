<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\MTProxy;

use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:mtproxy:sync-secrets',
    description: 'Sync active MTProxy secrets to secrets.txt file',
)]
final class SyncMtProxySecretsCommand extends Command
{
    public function __construct(
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
        private readonly string $secretsPath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $secrets = $this->vpnConnectionRepository->findAllActiveMtProxySecrets();
        $content = implode("\n", $secrets);

        if ($content !== '') {
            $content .= "\n";
        }

        file_put_contents($this->secretsPath, $content);

        $output->writeln(sprintf('Synced %d MTProxy secret(s) to %s', count($secrets), $this->secretsPath));

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Console;

use App\VPN\Domain\AWGProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:awg:test-connection',
    description: 'Create a test AWG peer, print its config and QR path, then delete it',
)]
final class TestAWGConnectionCommand extends Command
{
    public function __construct(private readonly AWGProviderInterface $awgProvider)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('keep', null, InputOption::VALUE_NONE, 'Do not delete the test peer after creation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name   = 'test_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $keep   = (bool) $input->getOption('keep');

        $io->section("Creating test peer: {$name}");

        $peerId = $this->awgProvider->createPeer($name);
        $io->success("Peer created: {$peerId}");

        $config = $this->awgProvider->getPeerConfig($peerId);
        $io->section('WireGuard config');
        $output->writeln($config);

        $qrPath = sys_get_temp_dir() . "/awg_test_{$peerId}.png";
        file_put_contents($qrPath, $this->awgProvider->generateQrPngFromConfig($config));
        $io->note("QR saved: {$qrPath}");

        if (!$keep) {
            $this->awgProvider->deletePeer($peerId);
            $io->comment("Test peer deleted.");
        } else {
            $io->warning("Peer kept. Delete manually: peer ID = {$peerId}");
        }

        return Command::SUCCESS;
    }
}

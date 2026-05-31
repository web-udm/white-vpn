<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Console;

use App\VPN\Infrastructure\Xui\XuiVPNProvider;
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
    public function __construct(private readonly XuiVPNProvider $vpnProvider)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subId = 'test-' . bin2hex(random_bytes(8));
        $inboundIds = $this->vpnProvider->getInboundIds();

        $output->writeln('SubID:      ' . $subId);
        $output->writeln('Inbounds:   ' . implode(', ', $inboundIds));
        $output->writeln('');

        try {
            $result = $this->vpnProvider->rawRequest('POST', '/panel/api/clients/add', [
                'json' => [
                    'client' => [
                        'email' => $subId,
                        'subId' => $subId,
                        'limitIp' => 1,
                        'totalGB' => 0,
                        'expiryTime' => 0,
                        'tgId' => 0,
                        'enable' => true,
                        'reset' => 0,
                    ],
                    'inboundIds' => $inboundIds,
                ],
            ]);

            $output->writeln('Status:  ' . $result['status']);
            $output->writeln('Headers: ' . json_encode($result['headers']));
            $output->writeln('Body:    ' . $result['body']);

            if ($result['status'] >= 200 && $result['status'] < 300) {
                $output->writeln('<info>OK</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<error>FAIL</error>');
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $output->writeln('<error>FAIL</error> — ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

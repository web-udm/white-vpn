<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Console;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\AWGPeerConfig;
use App\VPN\Port\GetAWGConfigsQuery;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:awg:send-configs-all',
    description: 'Send AWG .conf files and QR codes to all active subscribers with provisioned peers',
)]
final class SendAWGConfigsCommand extends Command
{
    use HandleTrait;

    public function __construct(
        private readonly Nutgram $bot,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly VpnConnectionRepositoryInterface $vpnConnectionRepository,
        MessageBusInterface $messageBus,
    ) {
        parent::__construct();
        $this->messageBus = $messageBus;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptions = $this->subscriptionRepository->findAllActive();
        $total         = count($subscriptions);
        $sent          = 0;
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

            if ($existing === []) {
                $output->writeln("  [SKIP] #{$subscriptionId}: no AWG peers provisioned yet");
                $skipped++;
                continue;
            }

            $user = $this->userRepository->findById($subscription->getUserId());
            if ($user === null) {
                $output->writeln("  [SKIP] #{$subscriptionId}: user not found");
                $skipped++;
                continue;
            }

            $telegramId = $user->getTelegramId()->value;

            try {
                /** @var AWGPeerConfig[] $configs */
                $configs = $this->handle(new GetAWGConfigsQuery($telegramId));

                foreach ($configs as $config) {
                    $this->sendConfig($telegramId, $config);
                    usleep(50_000);
                }

                $output->writeln("  [OK]   #{$subscriptionId}: sent " . count($configs) . " configs");
                $sent++;
            } catch (\Throwable $e) {
                $output->writeln("  [ERR]  #{$subscriptionId}: {$e->getMessage()}");
                $failed++;
            }

            usleep(100_000);
        }

        $output->writeln("Done. Sent: {$sent}, skipped: {$skipped}, failed: {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function sendConfig(int $telegramId, AWGPeerConfig $config): void
    {
        $confResource = fopen('php://temp', 'r+');
        fwrite($confResource, $config->configContent);
        rewind($confResource);

        $this->bot->sendDocument(
            document: InputFile::make($confResource, $config->filename),
            caption: $config->filename,
            chat_id: $telegramId,
        );

        $qrResource = fopen('php://temp', 'r+');
        fwrite($qrResource, $config->qrCodePng);
        rewind($qrResource);

        $this->bot->sendPhoto(
            photo: InputFile::make($qrResource, str_replace('.conf', '.png', $config->filename)),
            caption: 'QR для ' . $config->filename,
            chat_id: $telegramId,
        );
    }
}

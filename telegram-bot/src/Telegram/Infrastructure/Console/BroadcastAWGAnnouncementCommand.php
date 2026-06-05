<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Console;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\VPN\Domain\Entity\VpnConnection;
use App\VPN\Domain\Repository\VpnConnectionRepositoryInterface;
use App\VPN\Port\AWGPeerConfig;
use App\VPN\Port\CreateAWGConnectionsCommand;
use App\VPN\Port\GetAWGConfigsQuery;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:broadcast:awg-announcement')]
final class BroadcastAWGAnnouncementCommand extends Command
{
    use HandleTrait;

    private const string ANNOUNCEMENT = <<<TEXT
        🔐 *AmneziaWG — новый способ подключения*

        Мы добавили поддержку AmneziaWG \(обфусцированный WireGuard\) — работает там, где обычный VPN блокируется\.

        Ваши персональные конфиги уже созданы: *3 файла* для разных устройств\. Получите их в разделе *«🔑 Мои подключения»*\.

        ⚠️ *Старый WireGuard будет отключён в течение недели\.* Успейте подключить новый\.
        TEXT;

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
        $provisioned   = 0;
        $notified      = 0;
        $failed        = 0;

        $output->writeln("Found {$total} active subscriptions.");

        foreach ($subscriptions as $subscription) {
            $subscriptionId = $subscription->getId();
            if ($subscriptionId === null) {
                continue;
            }

            $user = $this->userRepository->findById($subscription->getUserId());
            if ($user === null) {
                $output->writeln("  [SKIP] subscription {$subscriptionId}: user not found");
                continue;
            }

            $telegramId = $user->getTelegramId()->value;

            try {
                $this->provisionAndNotify($subscriptionId, $telegramId, $output);
                $provisioned++;
                $notified++;
            } catch (\Throwable $e) {
                $failed++;
                $output->writeln("  [ERROR] subscription {$subscriptionId}: {$e->getMessage()}");
            }

            usleep(100_000);
        }

        $output->writeln("Done. Provisioned+notified: {$notified}, failed: {$failed}.");

        return Command::SUCCESS;
    }

    private function provisionAndNotify(int $subscriptionId, int $telegramId, OutputInterface $output): void
    {
        $existing = $this->vpnConnectionRepository->findByTypeAndSubscriptionId(
            VpnConnection::TYPE_AMNEZIA_WIREGUARD,
            $subscriptionId,
        );

        if (count($existing) < 3) {
            $this->messageBus->dispatch(new CreateAWGConnectionsCommand($subscriptionId));
            $output->writeln("  [OK] subscription {$subscriptionId}: peers created");
        } else {
            $output->writeln("  [SKIP] subscription {$subscriptionId}: peers already exist");
        }

        /** @var AWGPeerConfig[] $configs */
        $configs = $this->handle(new GetAWGConfigsQuery($telegramId));

        foreach ($configs as $config) {
            $this->sendConfig($telegramId, $config);
        }

        $this->bot->sendMessage(
            text: self::ANNOUNCEMENT,
            chat_id: $telegramId,
            parse_mode: 'MarkdownV2',
        );
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

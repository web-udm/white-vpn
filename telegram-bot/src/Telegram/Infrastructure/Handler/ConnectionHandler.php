<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\VPN\Port\AWGPeerConfig;
use App\VPN\Port\GetAWGConfigsQuery;
use App\VPN\Port\GetVpnConnectionURLsQuery;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConnectionHandler
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Nutgram $bot): void
    {
        /** @var int $telegramId guaranteed by middleware */
        $telegramId = $bot->userId();

        $bot->answerCallbackQuery();

        /** @var ?SubscriptionStatus $status */
        $status = $this->handle(new GetActiveSubscriptionQuery($telegramId));

        if ($status === null || $status->status !== SubscriptionStatus::STATUS_ACTIVE) {
            $bot->sendMessage('У вас нет активной подписки. Нажмите «Подключиться» чтобы подать заявку.');
            return;
        }

        /** @var string[] $urls */
        $urls = $this->handle(new GetVpnConnectionURLsQuery($telegramId));

        if ($urls !== []) {
            $bot->sendMessage($this->formatVpnUrls($urls), parse_mode: 'Markdown');
        }

        /** @var AWGPeerConfig[] $awgConfigs */
        $awgConfigs = $this->handle(new GetAWGConfigsQuery($telegramId));

        foreach ($awgConfigs as $awgConfig) {
            $this->sendAWGConfig($bot, $awgConfig);
        }

        if ($urls === [] && $awgConfigs === []) {
            $bot->sendMessage('Подключение не найдено. Обратитесь в поддержку.');
        }
    }

    /** @param string[] $urls */
    private function formatVpnUrls(array $urls): string
    {
        $lines = ['*VPN:*'];
        foreach ($urls as $url) {
            $lines[] = "`$url`";
        }

        return implode("\n", $lines);
    }

    private function sendAWGConfig(Nutgram $bot, AWGPeerConfig $config): void
    {
        $confResource = fopen('php://temp', 'r+');
        fwrite($confResource, $config->configContent);
        rewind($confResource);

        $bot->sendDocument(
            document: InputFile::make($confResource, $config->filename),
            caption: $config->filename,
        );

        $qrResource = fopen('php://temp', 'r+');
        fwrite($qrResource, $config->qrCodePng);
        rewind($qrResource);

        $bot->sendPhoto(
            photo: InputFile::make($qrResource, str_replace('.conf', '.png', $config->filename)),
            caption: 'QR для ' . $config->filename,
        );
    }
}

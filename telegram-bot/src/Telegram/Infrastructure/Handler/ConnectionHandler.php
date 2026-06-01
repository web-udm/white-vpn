<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
use App\VPN\Port\GetVpnConnectionURLsQuery;
use SergiX44\Nutgram\Nutgram;
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

        if ($urls === []) {
            $bot->sendMessage('Подключение не найдено. Обратитесь в поддержку.');
            return;
        }

        $lines = ['*VPN:*'];
        foreach ($urls as $url) {
            $lines[] = "`$url`";
        }

        $bot->sendMessage(implode("\n", $lines), parse_mode: 'Markdown');
    }
}

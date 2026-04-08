<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\VPN\Port\GetVpnConnectionURLQuery;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class ConnectionVlessHandler
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

        /** @var ?string $url */
        $url = $this->handle(new GetVpnConnectionURLQuery($telegramId, GetVpnConnectionURLQuery::PROTOCOL_VLESS));

        if ($url === null) {
            $bot->sendMessage('Подключение не найдено. Обратитесь в поддержку.');
            return;
        }

        $bot->sendMessage("Ваша ссылка для подключения VLESS:\n\n`$url`", parse_mode: 'Markdown');
    }
}

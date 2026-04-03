<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Entity\VPNConnection;
use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\GetVPNConnectionsQuery;
use App\VPN\Port\GetSubscriptionURLQuery;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class StatusHandler
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

        /** @var ?Subscription $subscription */
        $subscription = $this->handle(new GetActiveSubscriptionQuery($telegramId));

        if ($subscription === null) {
            $bot->sendMessage("Статус: НЕ АКТИВНА\n\nУ вас пока нет подписки. Нажмите «Подключиться» чтобы подать заявку.");
            return;
        }

        /** @var VPNConnection[] $connections */
        $connections = $this->handle(new GetVPNConnectionsQuery($telegramId));

        $bot->sendMessage($this->formatStatus($subscription, $connections));
    }

    /**
     * @param VPNConnection[] $connections
     */
    private function formatStatus(Subscription $subscription, array $connections): string
    {
        $quota = $subscription->getQuota();
        $activeCount = count(array_filter($connections, fn(VPNConnection $c) => $c->isActive()));
        $tier = $subscription->isVip() ? 'VIP' : 'Стандарт';

        $text = "Подписка: АКТИВНА\n";
        $text .= "Тариф: {$tier}\n";
        $text .= "Конфигов: {$activeCount}/{$quota}\n";

        if (empty($connections)) {
            $text .= "\nУ вас пока нет конфигов. Нажмите «Получить VPN».";
            return $text;
        }

        $text .= "\nВаши подключения:\n";

        foreach ($connections as $i => $connection) {
            $num = $i + 1;
            $status = $connection->isActive() ? 'активно' : 'отключено';

            if ($connection->isActive()) {
                /** @var string $url */
                $url = $this->handle(new GetSubscriptionURLQuery($connection->getSubId()));
                $text .= "\n#{$num} ({$status})\n{$url}\n";
            } else {
                $text .= "\n#{$num} ({$status})\n";
            }
        }

        return $text;
    }
}

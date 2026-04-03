<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Port\GetActiveSubscriptionQuery;
use App\Subscription\Port\SubscriptionStatus;
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

        /** @var ?SubscriptionStatus $status */
        $status = $this->handle(new GetActiveSubscriptionQuery($telegramId));

        $bot->sendMessage($this->formatStatus($status));
    }

    private function formatStatus(?SubscriptionStatus $status): string
    {
        if ($status === null) {
            return "Статус: НЕ АКТИВНА\n\nУ вас пока нет подписки. Нажмите «Подключиться» чтобы подать заявку.";
        }

        return match ($status->status) {
            'active' => $this->formatActive($status),
            'paused' => "Статус: ПРИОСТАНОВЛЕНА\n\nОбратитесь в поддержку.",
            'expired' => "Статус: ИСТЕКЛА\n\nОбратитесь в поддержку для продления.",
            default => "Статус: НЕИЗВЕСТЕН",
        };
    }

    private function formatActive(SubscriptionStatus $status): string
    {
        $text = "Статус: АКТИВНА\n";

        if ($status->expiresAt !== null) {
            $days = (int) (new \DateTimeImmutable())->diff($status->expiresAt)->days;
            $text .= "Осталось дней: $days\n";
            $text .= "Дата окончания: " . $status->expiresAt->format('d.m.Y') . "\n";
        } else {
            $text .= "Срок действия: бессрочно\n";
        }

        return $text;
    }
}

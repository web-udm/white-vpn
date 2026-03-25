<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\TelegramId;
use App\VPN\Port\GetSubscriptionStatusQuery;
use App\VPN\Port\GetSubscriptionURLQuery;
use App\VPN\Port\Subscription;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class StatusHandler
{
    use HandleTrait;

    private const string NO_CONNECTION = "Статус: НЕ АКТИВНА\n\nУ вас пока нет подключения. Нажмите «Подключиться» чтобы подать заявку.";

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Nutgram $bot): void
    {
        /** @var int $telegramId guaranteed by middleware */
        $telegramId = $bot->userId();

        $bot->answerCallbackQuery();

        $subID = $this->resolveSubID($telegramId);

        if ($subID === null) {
            $bot->sendMessage(self::NO_CONNECTION);
            return;
        }

        $status = $this->fetchStatus($bot, $subID);

        if ($status === null) {
            return;
        }

        $bot->sendMessage($this->formatStatus($status, $subID));
    }

    private function resolveSubID(int $telegramId): ?string
    {
        $user = $this->userRepository->findByTelegramId(new TelegramId($telegramId));

        return $user?->getSubId();
    }

    private function fetchStatus(Nutgram $bot, string $subID): ?Subscription
    {
        try {
            /** @var ?Subscription $status */
            $status = $this->handle(new GetSubscriptionStatusQuery($subID));
        } catch (\Throwable) {
            $bot->sendMessage('Сервис временно недоступен, попробуйте позже.');
            return null;
        }

        if ($status === null) {
            $bot->sendMessage(self::NO_CONNECTION);
        }

        return $status;
    }

    private function formatStatus(Subscription $status, string $subID): string
    {
        if ($status->isExpired()) {
            $expiryDate = $status->expiryDate()?->format('d.m.Y') ?? '—';
            return "Статус: ИСТЕКЛА\n\nДата окончания: $expiryDate";
        }

        if (!$status->enabled) {
            return "Статус: ОТКЛЮЧЕНА\n\nОбратитесь в поддержку.";
        }

        return $this->formatActive($status, $subID);
    }

    private function formatActive(Subscription $status, string $subID): string
    {
        $remainingDays = $status->remainingDays();
        $expiryDate = $status->expiryDate()?->format('d.m.Y');

        /** @var string $subscriptionURL */
        $subscriptionURL = $this->handle(new GetSubscriptionURLQuery($subID));

        $text = "Статус: АКТИВНА\n";

        if ($remainingDays !== null && $expiryDate !== null) {
            $text .= "Осталось дней: $remainingDays\n";
            $text .= "Дата окончания: $expiryDate\n";
        } else {
            $text .= "Срок действия: бессрочно\n";
        }

        $text .= "\nСсылка на подключение:\n$subscriptionURL";

        return $text;
    }
}
<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\ApproveConnectionRequestCommand;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdminApproveHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private readonly SubscriptionRequestRepositoryInterface $requestRepository,
        #[Autowire('%telegram.admin_id%')] private readonly int $adminTelegramId,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Nutgram $bot, string $requestId): void
    {
        $id = (int) $requestId;

        if (!$this->isAdmin($bot)) {
            return;
        }

        if (!$this->tryApprove($bot, $id)) {
            return;
        }

        $bot->answerCallbackQuery(text: 'Заявка одобрена!');
        $bot->editMessageText("Заявка #{$id} — одобрена");
        $this->notifyUser($bot, $id);
    }

    private function tryApprove(Nutgram $bot, int $id): bool
    {
        try {
            $this->handle(new ApproveConnectionRequestCommand($id));
            return true;
        } catch (\Throwable $e) {
            $bot->answerCallbackQuery(text: "Ошибка: {$e->getMessage()}", show_alert: true);
            return false;
        }
    }

    private function isAdmin(Nutgram $bot): bool
    {
        if ($bot->userId() === $this->adminTelegramId) {
            return true;
        }

        $bot->answerCallbackQuery(text: 'У вас нет прав для этого действия.', show_alert: true);

        return false;
    }

    private function notifyUser(Nutgram $bot, int $requestId): void
    {
        $request = $this->requestRepository->findById($requestId);

        if ($request !== null) {
            $bot->sendMessage(
                text: "Ваша заявка одобрена! Добро пожаловать.\n\nНажмите кнопку «Получить VPN» чтобы получить конфигурацию подключения.",
                chat_id: $request->getTelegramId(),
            );
        }
    }
}

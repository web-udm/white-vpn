<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Repository\SubscriptionRequestRepositoryInterface;
use App\Subscription\Port\RejectSubscriptionRequestCommand;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdminRejectHandler
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

        if (!$this->tryReject($bot, $id)) {
            return;
        }

        $bot->answerCallbackQuery(text: 'Заявка отклонена.');
        $bot->editMessageText("Заявка #{$id} — отклонена");
        $this->notifyUser($bot, $id);
    }

    private function isAdmin(Nutgram $bot): bool
    {
        if ($bot->userId() === $this->adminTelegramId) {
            return true;
        }

        $bot->answerCallbackQuery(text: 'У вас нет прав для этого действия.', show_alert: true);

        return false;
    }

    private function tryReject(Nutgram $bot, int $id): bool
    {
        try {
            $this->handle(new RejectSubscriptionRequestCommand($id));
            return true;
        } catch (\Throwable $e) {
            $bot->answerCallbackQuery(text: "Ошибка: {$e->getMessage()}", show_alert: true);
            return false;
        }
    }

    private function notifyUser(Nutgram $bot, int $requestId): void
    {
        $request = $this->requestRepository->findById($requestId);

        if ($request !== null) {
            $bot->sendMessage(
                text: 'К сожалению, ваша заявка на подключение отклонена. Если есть вопросы — обратитесь в поддержку.',
                chat_id: $request->getTelegramId(),
            );
        }
    }
}

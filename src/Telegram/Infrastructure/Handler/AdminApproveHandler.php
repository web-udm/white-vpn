<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Subscription\Domain\Repository\ConnectionRequestRepositoryInterface;
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
        private readonly ConnectionRequestRepositoryInterface $requestRepository,
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

        $subscriptionUrl = $this->tryApprove($bot, $id);

        if ($subscriptionUrl === null) {
            return;
        }

        $bot->answerCallbackQuery(text: 'Заявка одобрена!');
        $bot->editMessageText("Заявка #{$id} — одобрена");
        $this->notifyUser($bot, $id, $subscriptionUrl);
    }

    private function tryApprove(Nutgram $bot, int $id): ?string
    {
        try {
            /** @var string */
            return $this->handle(new ApproveConnectionRequestCommand($id));
        } catch (\Throwable $e) {
            $bot->answerCallbackQuery(text: "Ошибка: {$e->getMessage()}", show_alert: true);
            return null;
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

    private function notifyUser(Nutgram $bot, int $requestId, string $subscriptionUrl): void
    {
        $request = $this->requestRepository->findById($requestId);

        if ($request !== null) {
            $bot->sendMessage(
                text: "Ваша заявка одобрена! Добро пожаловать.\n\nСсылка на подключение:\n{$subscriptionUrl}",
                chat_id: $request->getTelegramId(),
            );
        }
    }
}

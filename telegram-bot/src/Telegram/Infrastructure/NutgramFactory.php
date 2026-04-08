<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure;

use App\Telegram\Infrastructure\Handler\AdminApproveHandler;
use App\Telegram\Infrastructure\Handler\AdminRejectHandler;
use App\Telegram\Infrastructure\Handler\ConnectionHandler;
use App\Telegram\Infrastructure\Handler\ConnectionVlessHandler;
use App\Telegram\Infrastructure\Handler\SubscriptionRequestHandler;
use App\Telegram\Infrastructure\Handler\MenuHandler;
use App\Telegram\Infrastructure\Handler\StartHandler;
use App\Telegram\Infrastructure\Handler\StatusHandler;
use App\Telegram\Infrastructure\Handler\SupportHandler;
use App\Telegram\Infrastructure\Keyboard\MainMenu;
use Psr\Log\LoggerInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NutgramFactory
{
    public function __construct(
        #[Autowire('%telegram.bot_token%')] private string $token,
        private LoggerInterface $logger,
        private StartHandler $startHandler,
        private MenuHandler $menuHandler,
        private SubscriptionRequestHandler $connectHandler,
        private StatusHandler $statusHandler,
        private SupportHandler $supportHandler,
        private AdminApproveHandler $adminApproveHandler,
        private AdminRejectHandler $adminRejectHandler,
        private ConnectionHandler $connectionHandler,
        private ConnectionVlessHandler $connectionVlessHandler,
    ) {
    }

    public function create(): Nutgram
    {
        if ($this->token === '') {
            throw new \RuntimeException('BOT_TOKEN is not set. Please configure it in .env');
        }

        $bot = new Nutgram($this->token, new Configuration());

        $bot->middleware(function (Nutgram $bot, $next): void {
            if ($bot->userId() !== null) {
                $next($bot);
            }
        });

        $bot->onException(function (Nutgram $bot, \Throwable $e): void {
            $this->logger->error('Unhandled Nutgram exception', [
                'message' => $e->getMessage(),
                'class' => $e::class,
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
        });

        $bot->onCommand('start', $this->startHandler);
        $bot->onText(MainMenu::MENU_BUTTON_TEXT, $this->menuHandler);
        $bot->onCallbackQueryData(MainMenu::CONNECT, $this->connectHandler);
        $bot->onCallbackQueryData(MainMenu::STATUS, $this->statusHandler);
        $bot->onCallbackQueryData(MainMenu::SUPPORT, $this->supportHandler);
        $bot->onCallbackQueryData(MainMenu::GET_CONNECTION, $this->connectionHandler);
        $bot->onCallbackQueryData(MainMenu::CONNECTION_VLESS, $this->connectionVlessHandler);
        $bot->onCallbackQueryData('approve_{requestId}', $this->adminApproveHandler);
        $bot->onCallbackQueryData('reject_{requestId}', $this->adminRejectHandler);

        return $bot;
    }
}

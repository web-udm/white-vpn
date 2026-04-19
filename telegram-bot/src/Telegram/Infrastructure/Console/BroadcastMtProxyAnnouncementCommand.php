<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Console;

use App\User\Domain\Repository\UserRepositoryInterface;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:broadcast:mtproxy-announcement')]
final class BroadcastMtProxyAnnouncementCommand extends Command
{
    private const string MESSAGE = <<<TEXT
        🎉 *Новая функция: MTProxy*

        Теперь у каждого подписчика есть личный MTProxy — прокси\-сервер для Telegram, который позволяет пользоваться мессенджером без VPN\.

        *Как получить:*
        Нажмите *«🔑 Мои подключения»* в главном меню — там появится раздел MTProxy с вашей персональной ссылкой\.

        *Как подключить:*
        1\. Нажмите на ссылку — Telegram спросит «Включить прокси?»
        2\. Нажмите «Включить» — готово\!

        _MTProxy работает только для Telegram и не заменяет VPN полностью\._
        TEXT;

    public function __construct(
        private readonly Nutgram $bot,
        private readonly UserRepositoryInterface $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $telegramIds = $this->userRepository->findAllTelegramIds();
        $total = count($telegramIds);
        $sent = 0;
        $failed = 0;

        $output->writeln("Broadcasting to {$total} users...");

        foreach ($telegramIds as $telegramId) {
            try {
                $this->bot->sendMessage(
                    text: self::MESSAGE,
                    chat_id: $telegramId,
                    parse_mode: 'MarkdownV2',
                );
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }

            usleep(50_000); // 50ms — ~20 msg/sec, безопасно для Telegram rate limit
        }

        $output->writeln("Done. Sent: {$sent}, failed: {$failed}.");

        return Command::SUCCESS;
    }
}

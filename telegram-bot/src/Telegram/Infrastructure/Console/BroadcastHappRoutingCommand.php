<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Console;

use App\User\Domain\Repository\UserRepositoryInterface;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:broadcast:happ-routing')]
final class BroadcastHappRoutingCommand extends Command
{
    private const string MESSAGE = <<<TEXT
        🇷🇺 <b>Обход российских сайтов в Happ</b>

        Теперь сайты Сбербанка, Яндекса, Госуслуг и других российских ресурсов открываются напрямую, без VPN.

        <b>Как включить:</b>
        Нажмите на ссылку ниже — Happ откроется и применит настройки автоматически. В приложении появится оповещение «загрузка гео-файлов». Дождитесь, пока оно пропадёт, и перезапустите подключение.

        После этого попробуйте зайти на сайт Ozon или Kinopoisk, например. Если что-то не получилось - пишите мне @moildar

        <a href="https://sub.whitevpn.tech/happ-routing">👆 Применить настройки в Happ</a>

        <i>Работает только в приложении Happ. Если вы используете другое - пожалуйста, переключитесь на Happ</i>
        TEXT;

    public function __construct(
        private readonly Nutgram $bot,
        private readonly UserRepositoryInterface $userRepository,
        #[Autowire('%telegram.admin_id%')] private readonly int $adminTelegramId,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('admin-only', null, InputOption::VALUE_NONE, 'Send only to admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $telegramIds = $input->getOption('admin-only')
            ? [$this->adminTelegramId]
            : $this->userRepository->findAllTelegramIds();
        $total = count($telegramIds);
        $sent = 0;
        $failed = 0;

        $output->writeln("Broadcasting to {$total} users...");

        foreach ($telegramIds as $telegramId) {
            try {
                $this->bot->sendMessage(
                    text: self::MESSAGE,
                    chat_id: $telegramId,
                    parse_mode: 'HTML',
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $output->writeln("Failed to send to {$telegramId}: " . $e->getMessage());
            }

            usleep(50_000);
        }

        $output->writeln("Done. Sent: {$sent}, failed: {$failed}.");

        return Command::SUCCESS;
    }
}

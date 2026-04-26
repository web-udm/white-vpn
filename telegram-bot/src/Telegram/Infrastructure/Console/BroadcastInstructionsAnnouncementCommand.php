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

#[AsCommand(name: 'app:broadcast:instructions-announcement')]
final class BroadcastInstructionsAnnouncementCommand extends Command
{
    private const string MESSAGE = <<<TEXT
        У нас появился новый раздел — *«📖 Инструкции»*\.

        Туда будут складываться полезные статьи по настройке и оптимизации\.

        Пока что там одна статья, зато полезная - про то, как добавить приложения в "игнор" и не использовать VPN на них\.
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
        $adminOnly = (bool) $input->getOption('admin-only');

        $telegramIds = $adminOnly
            ? [$this->adminTelegramId]
            : $this->userRepository->findAllTelegramIds();

        $total = count($telegramIds);
        $sent = 0;
        $failed = 0;

        $output->writeln("Broadcasting to {$total} user(s)...");

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

            usleep(50_000);
        }

        $output->writeln("Done. Sent: {$sent}, failed: {$failed}.");

        return Command::SUCCESS;
    }
}

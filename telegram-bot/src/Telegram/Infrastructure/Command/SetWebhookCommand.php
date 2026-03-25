<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command;

use SergiX44\Nutgram\Nutgram;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:telegram:set-webhook',
    description: 'Register webhook URL in Telegram Bot API',
)]
final class SetWebhookCommand extends Command
{
    public function __construct(private readonly Nutgram $bot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('url', null, InputOption::VALUE_REQUIRED, 'Webhook URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getOption('url');

        if ($url === null || $url === '') {
            $output->writeln('<error>Please provide --url option</error>');
            return Command::FAILURE;
        }

        $this->bot->setWebhook($url);
        $output->writeln("<info>Webhook set to: {$url}</info>");

        return Command::SUCCESS;
    }
}

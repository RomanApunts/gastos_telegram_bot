<?php

namespace App\Command;

use App\Repository\TelegramUserRepository;
use App\Telegram\CommandRouter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prueba los comandos del bot en local sin pasar por Telegram.
 * Ej: php bin/console app:bot:simulate 123456 /gasto 12,50 Comida menú
 */
#[AsCommand(
    name: 'app:bot:simulate',
    description: 'Simula un mensaje entrante y muestra la respuesta del bot',
)]
final class SimulateBotCommand extends Command
{
    public function __construct(
        private readonly TelegramUserRepository $users,
        private readonly CommandRouter $router,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('telegram-id', InputArgument::REQUIRED, 'ID de Telegram del remitente (debe estar autorizado)')
            ->addArgument('message', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'El mensaje, ej: /gasto 12 Comida');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $telegramId = trim((string) $input->getArgument('telegram-id'));
        $text = implode(' ', $input->getArgument('message'));

        $user = $this->users->findActiveByTelegramId($telegramId);
        if ($user === null) {
            $io->error("No hay un usuario autorizado con ID {$telegramId}. Añádelo con app:user:add.");

            return Command::FAILURE;
        }

        $io->section("➡️  {$user->getName()}: {$text}");
        $io->writeln($this->router->dispatch($user, $telegramId, $text));

        return Command::SUCCESS;
    }
}

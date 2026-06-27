<?php

namespace App\Command;

use App\Entity\TelegramUser;
use App\Repository\TelegramUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:add',
    description: 'Autoriza a un usuario de Telegram a usar el bot (whitelist)',
)]
final class AddUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TelegramUserRepository $users,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('telegram-id', InputArgument::REQUIRED, 'ID numérico de Telegram del usuario')
            ->addArgument('name', InputArgument::REQUIRED, 'Nombre para identificarlo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $telegramId = trim((string) $input->getArgument('telegram-id'));
        $name = trim((string) $input->getArgument('name'));

        if (!ctype_digit($telegramId)) {
            $io->error('El ID de Telegram debe ser numérico.');

            return Command::INVALID;
        }

        $existing = $this->users->findOneBy(['telegramId' => $telegramId]);
        if ($existing !== null) {
            $existing->setName($name)->setActive(true);
            $this->em->flush();
            $io->success("Usuario «{$name}» ({$telegramId}) actualizado y activo.");

            return Command::SUCCESS;
        }

        $this->em->persist(new TelegramUser($telegramId, $name));
        $this->em->flush();
        $io->success("Usuario «{$name}» ({$telegramId}) autorizado.");

        return Command::SUCCESS;
    }
}

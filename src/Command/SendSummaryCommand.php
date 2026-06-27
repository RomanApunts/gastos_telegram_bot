<?php

namespace App\Command;

use App\Service\Notifier;
use App\Service\SummaryBuilder;
use App\Telegram\Util\Months;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Envía el resumen del periodo a todos los usuarios. Pensado para cron:
 *   0 20 * * 0       app:summary:send            (cada domingo, mes actual)
 *   30 9 1 * *       app:summary:send pasado     (día 1, cierre del mes anterior)
 */
#[AsCommand(
    name: 'app:summary:send',
    description: 'Envía el resumen del mes a todos los usuarios (actual, pasado o MM/AAAA)',
)]
final class SendSummaryCommand extends Command
{
    public function __construct(
        private readonly SummaryBuilder $builder,
        private readonly Notifier $notifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('period', InputArgument::OPTIONAL, 'actual | pasado | MM/AAAA', 'actual');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $period = Months::resolve((string) $input->getArgument('period'));
        if ($period === null) {
            $io->error('Periodo no válido. Usa: actual, pasado o MM/AAAA.');

            return Command::INVALID;
        }

        $text = $this->builder->build($period);
        $this->notifier->broadcast("🗓️ Resumen automático\n\n" . $text);

        $io->success("Resumen de {$period['label']} enviado.");

        return Command::SUCCESS;
    }
}

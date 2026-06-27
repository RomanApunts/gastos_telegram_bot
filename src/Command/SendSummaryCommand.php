<?php

namespace App\Command;

use App\Service\SummaryReporter;
use App\Telegram\Util\Months;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Envía el informe del periodo (texto + gráficos) a todos los usuarios.
 * Pensado para cron:
 *   0 20 * * 0       app:summary:send            (cada domingo, mes actual)
 *   30 9 1 * *       app:summary:send pasado     (día 1, cierre del mes anterior)
 */
#[AsCommand(
    name: 'app:summary:send',
    description: 'Envía el informe del mes (texto + gráficos) a todos los usuarios',
)]
final class SendSummaryCommand extends Command
{
    public function __construct(
        private readonly SummaryReporter $reporter,
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

        $recipients = $this->reporter->broadcast($period, "🗓️ Resumen automático\n\n");

        $io->success("Informe de {$period['label']} enviado a {$recipients} usuario(s).");

        return Command::SUCCESS;
    }
}

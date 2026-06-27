<?php

namespace App\Command;

use App\Entity\Expense;
use App\Service\Notifier;
use App\Service\RecurringExpenseRunner;
use App\Telegram\Util\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera los gastos recurrentes que toquen hoy. Pensado para cron diario:
 *   0 6 * * *  php /ruta/bin/console app:recurring:run
 */
#[AsCommand(
    name: 'app:recurring:run',
    description: 'Crea los gastos recurrentes cuyo día haya llegado este mes',
)]
final class RunRecurringCommand extends Command
{
    public function __construct(
        private readonly RecurringExpenseRunner $runner,
        private readonly Notifier $notifier,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $created = $this->runner->run(new \DateTimeImmutable('today'));
        if ($created === []) {
            $io->writeln('Sin gastos recurrentes pendientes hoy.');

            return Command::SUCCESS;
        }

        $lines = array_map(
            fn (Expense $e) => "• " . Money::format($e->getAmount()) . " en {$e->getCategory()->getName()}",
            $created,
        );
        $this->notifier->broadcast("🔁 Gastos fijos registrados automáticamente:\n" . implode("\n", $lines));

        $io->success(count($created) . ' gasto(s) recurrente(s) creado(s).');

        return Command::SUCCESS;
    }
}

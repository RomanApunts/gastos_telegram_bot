<?php

namespace App\Command;

use App\Entity\Expense;
use App\Entity\Income;
use App\Service\Notifier;
use App\Service\RecurringExpenseRunner;
use App\Service\RecurringIncomeRunner;
use App\Telegram\Util\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera los movimientos recurrentes (gastos e ingresos) que toquen hoy.
 * Pensado para cron diario:
 *   0 6 * * *  php /ruta/bin/console app:recurring:run
 */
#[AsCommand(
    name: 'app:recurring:run',
    description: 'Crea los gastos e ingresos recurrentes cuyo día haya llegado este mes',
)]
final class RunRecurringCommand extends Command
{
    public function __construct(
        private readonly RecurringExpenseRunner $expenseRunner,
        private readonly RecurringIncomeRunner $incomeRunner,
        private readonly Notifier $notifier,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTimeImmutable('today');

        $expenses = $this->expenseRunner->run($today);
        $incomes = $this->incomeRunner->run($today);

        if ($expenses === [] && $incomes === []) {
            $io->writeln('Sin movimientos recurrentes pendientes hoy.');

            return Command::SUCCESS;
        }

        if ($expenses !== []) {
            $lines = array_map(
                fn (Expense $e) => "• " . Money::format($e->getAmount()) . " en {$e->getCategory()->getName()} ({$e->getDescription()})",
                $expenses,
            );
            $this->notifier->broadcast("🔁 Gastos fijos registrados automáticamente:\n" . implode("\n", $lines));
        }

        if ($incomes !== []) {
            $lines = array_map(
                fn (Income $i) => "• " . Money::format($i->getAmount()) . ($i->getDescription() !== null ? " ({$i->getDescription()})" : ''),
                $incomes,
            );
            $this->notifier->broadcast("🔁 Ingresos fijos registrados automáticamente:\n" . implode("\n", $lines));
        }

        $io->success(
            count($expenses) . ' gasto(s) y ' . count($incomes) . ' ingreso(s) recurrente(s) creado(s).'
        );

        return Command::SUCCESS;
    }
}

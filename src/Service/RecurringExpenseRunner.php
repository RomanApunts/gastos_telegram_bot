<?php

namespace App\Service;

use App\Entity\Expense;
use App\Repository\RecurringExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Genera los gastos reales a partir de las plantillas recurrentes.
 * Pensado para ejecutarse a diario por cron: crea el gasto cuando llega su día
 * y aún no se ha generado este mes (idempotente vía lastRunPeriod).
 */
final class RecurringExpenseRunner
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurring,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return Expense[] los gastos creados en esta ejecución
     */
    public function run(\DateTimeImmutable $today): array
    {
        $period = $today->format('Y-m');
        $daysInMonth = (int) $today->format('t');
        $todayDay = (int) $today->format('j');

        $created = [];
        foreach ($this->recurring->findActiveOrdered() as $template) {
            if ($template->getLastRunPeriod() === $period) {
                continue; // ya generado este mes
            }

            // Resuelve el día concreto de este mes: valores negativos cuentan
            // desde el final (-1 = último día) y los positivos que se pasan del
            // mes (ej. 31 en febrero) se ajustan al último día.
            $effectiveDay = $template->resolveDayFor($daysInMonth);
            if ($todayDay < $effectiveDay) {
                continue; // todavía no toca
            }

            $spentAt = $today->setDate(
                (int) $today->format('Y'),
                (int) $today->format('n'),
                $effectiveDay
            )->setTime(0, 0, 0);

            $expense = new Expense(
                $template->getCategory(),
                $template->getUser(),
                $template->getAmount(),
                $spentAt,
                $template->getDescription(),
            );
            $this->em->persist($expense);
            $template->setLastRunPeriod($period);
            $created[] = $expense;
        }

        if ($created !== []) {
            $this->em->flush();
        }

        return $created;
    }
}

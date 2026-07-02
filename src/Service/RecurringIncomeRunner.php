<?php

namespace App\Service;

use App\Entity\Income;
use App\Repository\RecurringIncomeRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Genera los ingresos reales a partir de las plantillas recurrentes.
 * Pensado para ejecutarse a diario por cron: crea el ingreso cuando llega su día
 * y aún no se ha generado este mes (idempotente vía lastRunPeriod).
 */
final class RecurringIncomeRunner
{
    public function __construct(
        private readonly RecurringIncomeRepository $recurring,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return Income[] los ingresos creados en esta ejecución
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

            $receivedAt = $today->setDate(
                (int) $today->format('Y'),
                (int) $today->format('n'),
                $effectiveDay
            )->setTime(0, 0, 0);

            $income = new Income(
                $template->getUser(),
                $template->getAmount(),
                $receivedAt,
                $template->getDescription(),
            );
            $this->em->persist($income);
            $template->setLastRunPeriod($period);
            $created[] = $income;
        }

        if ($created !== []) {
            $this->em->flush();
        }

        return $created;
    }
}

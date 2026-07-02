<?php

namespace App\Repository;

use App\Entity\RecurringIncome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringIncome>
 */
class RecurringIncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringIncome::class);
    }

    /**
     * Activos en orden cronológico dentro del mes: primero los días fijos
     * (1..31) y después los relativos al final (… penúltimo, último).
     *
     * @return RecurringIncome[]
     */
    public function findActiveOrdered(): array
    {
        $items = $this->findBy(['active' => true]);
        usort($items, fn (RecurringIncome $a, RecurringIncome $b) => $this->rank($a) <=> $this->rank($b));

        return $items;
    }

    /** Días positivos quedan antes (1..31); los negativos después y ordenados -28..-1. */
    private function rank(RecurringIncome $r): int
    {
        $day = $r->getDayOfMonth();

        return $day >= 1 ? $day : 1000 + $day;
    }
}

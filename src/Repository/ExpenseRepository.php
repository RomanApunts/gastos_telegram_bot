<?php

namespace App\Repository;

use App\Entity\Expense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    /**
     * Total gastado por categoría dentro de un mes.
     *
     * @return array<int, string> mapa category_id => total (string decimal)
     */
    public function sumByCategoryForPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.category) AS categoryId', 'SUM(e.amount) AS total')
            ->where('e.spentAt >= :from')
            ->andWhere('e.spentAt < :to')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->groupBy('e.category')
            ->getQuery()
            ->getResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['categoryId']] = $row['total'] ?? '0';
        }

        return $totals;
    }

    /**
     * Últimos gastos registrados, del más reciente al más antiguo.
     *
     * @return Expense[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

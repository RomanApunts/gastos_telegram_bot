<?php

namespace App\Repository;

use App\Entity\Income;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    /** Total ingresado dentro de un periodo. */
    public function sumForPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $total = $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.amount), 0)')
            ->where('i.receivedAt >= :from')
            ->andWhere('i.receivedAt < :to')
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return (string) $total;
    }

    /**
     * @return Income[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

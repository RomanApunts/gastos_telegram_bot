<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\CategoryBudget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryBudget>
 */
class CategoryBudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryBudget::class);
    }

    /**
     * Límite vigente de una categoría para un mes dado: la fila con el
     * effectiveFrom más reciente que sea <= primer día del mes.
     */
    public function findEffectiveForMonth(Category $category, \DateTimeImmutable $monthStart): ?CategoryBudget
    {
        return $this->createQueryBuilder('b')
            ->where('b.category = :category')
            ->andWhere('b.effectiveFrom <= :monthStart')
            ->setParameter('category', $category)
            ->setParameter('monthStart', $monthStart->format('Y-m-d'))
            ->orderBy('b.effectiveFrom', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Límite vigente de TODAS las categorías para un mes, en una sola pasada.
     *
     * @return array<int, string> mapa category_id => importe límite (string decimal)
     */
    public function findEffectiveAmountsForMonth(\DateTimeImmutable $monthStart): array
    {
        // Trae todas las filas vigentes ordenadas; nos quedamos con la más
        // reciente por categoría (la primera que aparece al ir desc).
        $rows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.category) AS categoryId', 'b.amount AS amount', 'b.effectiveFrom AS effectiveFrom')
            ->where('b.effectiveFrom <= :monthStart')
            ->setParameter('monthStart', $monthStart->format('Y-m-d'))
            ->orderBy('b.effectiveFrom', 'DESC')
            ->getQuery()
            ->getResult();

        $amounts = [];
        foreach ($rows as $row) {
            $catId = (int) $row['categoryId'];
            if (!isset($amounts[$catId])) {
                $amounts[$catId] = $row['amount'];
            }
        }

        return $amounts;
    }
}

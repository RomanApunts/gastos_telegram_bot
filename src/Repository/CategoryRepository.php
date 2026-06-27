<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /** @return Category[] */
    public function findActiveOrdered(): array
    {
        return $this->findBy(['active' => true], ['name' => 'ASC']);
    }

    public function findOneByName(string $name): ?Category
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** Busca una categoría por nombre ignorando mayúsculas/minúsculas. */
    public function findOneByNameInsensitive(string $name): ?Category
    {
        foreach ($this->findBy([]) as $category) {
            if (mb_strtolower($category->getName()) === mb_strtolower($name)) {
                return $category;
            }
        }

        return null;
    }
}

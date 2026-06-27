<?php

namespace App\Entity;

use App\Repository\CategoryBudgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Límite mensual de gasto para una categoría, con histórico.
 *
 * Cada cambio de máximo crea una fila nueva con su `effectiveFrom`.
 * El límite vigente para un mes M = la fila de esa categoría con el
 * `effectiveFrom` más reciente que sea <= primer día de M.
 * Así nunca se pierde el histórico de configuración.
 */
#[ORM\Entity(repositoryClass: CategoryBudgetRepository::class)]
#[ORM\Index(columns: ['category_id', 'effective_from'], name: 'idx_budget_cat_from')]
class CategoryBudget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    /** Importe máximo mensual en euros. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    /** Primer día del mes a partir del cual aplica este límite. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Category $category,
        string $amount,
        \DateTimeImmutable $effectiveFrom,
    ) {
        $this->category = $category;
        $this->amount = $amount;
        $this->effectiveFrom = $effectiveFrom;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getEffectiveFrom(): \DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

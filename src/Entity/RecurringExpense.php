<?php

namespace App\Entity;

use App\Repository\RecurringExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plantilla de gasto recurrente (ej: alquiler el día 1, Netflix el día 5).
 * Un proceso programado crea el Expense real el día indicado de cada mes.
 */
#[ORM\Entity(repositoryClass: RecurringExpenseRepository::class)]
class RecurringExpense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    /** A quién se le atribuye el gasto generado. */
    #[ORM\ManyToOne(targetEntity: TelegramUser::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TelegramUser $user;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /** Día del mes (1-31) en que se genera el gasto. */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $dayOfMonth;

    #[ORM\Column]
    private bool $active = true;

    /** Último mes ya procesado, formato "YYYY-MM", para no duplicar. */
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $lastRunPeriod = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Category $category,
        TelegramUser $user,
        string $amount,
        int $dayOfMonth,
        ?string $description = null,
    ) {
        $this->category = $category;
        $this->user = $user;
        $this->amount = $amount;
        $this->dayOfMonth = $dayOfMonth;
        $this->description = $description;
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

    public function getUser(): TelegramUser
    {
        return $this->user;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDayOfMonth(): int
    {
        return $this->dayOfMonth;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getLastRunPeriod(): ?string
    {
        return $this->lastRunPeriod;
    }

    public function setLastRunPeriod(?string $lastRunPeriod): self
    {
        $this->lastRunPeriod = $lastRunPeriod;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

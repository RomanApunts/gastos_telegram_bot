<?php

namespace App\Entity;

use App\Repository\RecurringIncomeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plantilla de ingreso recurrente (ej: nómina el día 1, alquiler cobrado el día 5).
 * Un proceso programado crea el Income real el día indicado de cada mes.
 * A diferencia de RecurringExpense, no lleva categoría (los ingresos no la tienen).
 */
#[ORM\Entity(repositoryClass: RecurringIncomeRepository::class)]
class RecurringIncome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** A quién se le atribuye el ingreso generado. */
    #[ORM\ManyToOne(targetEntity: TelegramUser::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TelegramUser $user;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /** Día del mes (1-31) en que se genera el ingreso. */
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
        TelegramUser $user,
        string $amount,
        int $dayOfMonth,
        ?string $description = null,
    ) {
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

    /**
     * Descripción legible del día de cobro.
     * Positivo = día fijo; negativo = contando desde el final (-1 = último).
     */
    public function getDayLabel(): string
    {
        $d = $this->dayOfMonth;
        if ($d >= 1) {
            return "el día {$d} de cada mes";
        }
        if ($d === -1) {
            return 'el último día de cada mes';
        }
        if ($d === -2) {
            return 'el penúltimo día de cada mes';
        }

        return 'el ' . abs($d) . 'º día desde el final de cada mes';
    }

    /**
     * Convierte el día configurado en el día concreto (1..n) de un mes dado,
     * resolviendo los valores negativos desde el final.
     */
    public function resolveDayFor(int $daysInMonth): int
    {
        $day = $this->dayOfMonth < 0
            ? $daysInMonth + $this->dayOfMonth + 1  // -1 => último día
            : $this->dayOfMonth;

        return max(1, min($day, $daysInMonth));
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

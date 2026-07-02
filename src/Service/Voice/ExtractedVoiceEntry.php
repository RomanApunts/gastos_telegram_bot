<?php

namespace App\Service\Voice;

/**
 * Movimiento (gasto o ingreso) interpretado a partir de una nota de voz.
 */
final class ExtractedVoiceEntry
{
    public const TYPE_EXPENSE = 'gasto';
    public const TYPE_INCOME = 'ingreso';

    public function __construct(
        /** "gasto" o "ingreso". */
        public readonly string $type,
        /** Importe como string decimal "12.50", o null si no se pudo entender. */
        public readonly ?string $amount,
        public readonly ?\DateTimeImmutable $date,
        /** Nombre de categoría sugerida (solo gastos), o null. */
        public readonly ?string $category,
        /** Concepto breve (ej. "gasolina", "nómina"), o null. */
        public readonly ?string $description,
        /** Lo que el motor entendió del audio (para mostrarlo al usuario). */
        public readonly ?string $transcript = null,
        /** Confianza 0..1 en la extracción del importe. */
        public readonly float $confidence = 0.0,
    ) {
    }

    public function hasAmount(): bool
    {
        return $this->amount !== null;
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }
}

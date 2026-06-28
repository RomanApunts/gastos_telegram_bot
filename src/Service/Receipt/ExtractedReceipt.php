<?php

namespace App\Service\Receipt;

/**
 * Datos extraídos de la foto de un ticket.
 */
final class ExtractedReceipt
{
    public function __construct(
        /** Importe total como string decimal "12.50", o null si no se pudo leer. */
        public readonly ?string $amount,
        public readonly ?\DateTimeImmutable $date,
        public readonly ?string $merchant,
        /** Nombre de categoría sugerida (debe existir), o null. */
        public readonly ?string $category,
        /** Confianza 0..1 en la extracción del importe. */
        public readonly float $confidence = 0.0,
    ) {
    }

    public function hasAmount(): bool
    {
        return $this->amount !== null;
    }
}

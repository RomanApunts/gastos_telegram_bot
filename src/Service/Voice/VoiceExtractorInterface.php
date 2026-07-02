<?php

namespace App\Service\Voice;

/**
 * Interpreta una nota de voz y la convierte en un movimiento (gasto o ingreso).
 * Implementación intercambiable (Gemini, Whisper + LLM, Claude…).
 */
interface VoiceExtractorInterface
{
    /**
     * @param string             $audioBytes    contenido binario del audio
     * @param string             $mimeType      ej. "audio/ogg"
     * @param string[]           $categoryNames categorías existentes (para gastos)
     * @param \DateTimeImmutable $today         referencia para resolver fechas relativas ("ayer")
     *
     * @throws \RuntimeException si la interpretación falla
     */
    public function extract(
        string $audioBytes,
        string $mimeType,
        array $categoryNames,
        \DateTimeImmutable $today,
    ): ExtractedVoiceEntry;
}

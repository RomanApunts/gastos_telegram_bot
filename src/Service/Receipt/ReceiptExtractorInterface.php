<?php

namespace App\Service\Receipt;

/**
 * Extrae los datos de un gasto a partir de la imagen de un ticket.
 * Implementación intercambiable (Gemini, Tesseract, Claude…).
 */
interface ReceiptExtractorInterface
{
    /**
     * @param string   $imageBytes    contenido binario de la imagen
     * @param string   $mimeType      ej. "image/jpeg"
     * @param string[] $categoryNames categorías existentes para que el motor elija una
     *
     * @throws \RuntimeException si la extracción falla
     */
    public function extract(string $imageBytes, string $mimeType, array $categoryNames): ExtractedReceipt;
}

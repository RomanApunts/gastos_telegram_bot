<?php

namespace App\Telegram\Util;

/**
 * Parseo y formateo de importes monetarios.
 * Internamente trabajamos con strings decimales de 2 posiciones (ej "12.50").
 */
final class Money
{
    /**
     * Convierte la entrada del usuario ("12,50", "12.50", "1.234,56", "10€")
     * en un string decimal normalizado "1234.56". Devuelve null si no es válido
     * o si no es un importe positivo.
     */
    public static function parse(string $raw): ?string
    {
        $raw = str_replace(['€', ' ', "\u{00a0}"], '', trim($raw));
        if ($raw === '') {
            return null;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            // El separador decimal es el que aparece más a la derecha.
            if (strrpos($raw, ',') > strrpos($raw, '.')) {
                $raw = str_replace(['.', ','], ['', '.'], $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($hasComma) {
            $raw = str_replace(',', '.', $raw);
        }

        if (!preg_match('/^\d+(\.\d+)?$/', $raw)) {
            return null;
        }
        if (bccomp($raw, '0', 4) <= 0) {
            return null;
        }

        return bcadd($raw, '0', 2);
    }

    /** Formatea un importe decimal a "1.234,56 €". */
    public static function format(string $amount): string
    {
        return number_format((float) $amount, 2, ',', '.') . ' €';
    }
}

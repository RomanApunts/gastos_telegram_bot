<?php

namespace App\Telegram\Util;

/**
 * Reconoce fechas escritas por el usuario: "hoy", "ayer", "anteayer",
 * "dd/mm", "dd-mm", "dd/mm/aaaa". Devuelve null si el texto no es una fecha.
 */
final class Dates
{
    public static function parse(string $token): ?\DateTimeImmutable
    {
        $t = mb_strtolower(trim($token));
        if ($t === '') {
            return null;
        }

        $today = new \DateTimeImmutable('today');

        return match ($t) {
            'hoy' => $today,
            'ayer' => $today->modify('-1 day'),
            'anteayer' => $today->modify('-2 days'),
            default => self::parseNumeric($t, $today),
        };
    }

    private static function parseNumeric(string $t, \DateTimeImmutable $today): ?\DateTimeImmutable
    {
        if (!preg_match('#^(\d{1,2})[/\-.](\d{1,2})(?:[/\-.](\d{2,4}))?$#', $t, $m)) {
            return null;
        }

        $day = (int) $m[1];
        $month = (int) $m[2];
        $yearGiven = isset($m[3]) && $m[3] !== '';
        $year = $yearGiven ? (int) $m[3] : (int) $today->format('Y');
        if ($year < 100) {
            $year += 2000;
        }

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        $date = $today->setDate($year, $month, $day)->setTime(0, 0, 0);

        // Sin año explícito y fecha en el futuro → se asume el año pasado.
        if (!$yearGiven && $date > $today) {
            $date = $date->modify('-1 year');
        }

        return $date;
    }
}

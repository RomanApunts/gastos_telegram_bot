<?php

namespace App\Telegram\Util;

/**
 * Resolución de periodos mensuales para los resúmenes.
 */
final class Months
{
    private const NAMES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /**
     * Mes actual.
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string}
     */
    public static function current(): array
    {
        return self::fromStart(self::firstDayOfThisMonth());
    }

    /**
     * Resuelve el argumento de un comando de resumen.
     * Acepta: vacío (mes actual), "pasado"/"anterior" (mes pasado),
     * "MM/AAAA", "MM-AAAA" o "AAAA-MM". Devuelve null si el formato no es válido.
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string}|null
     */
    public static function resolve(string $arg): ?array
    {
        $arg = trim(mb_strtolower($arg));

        if ($arg === '' || in_array($arg, ['actual', 'este', 'este mes'], true)) {
            return self::current();
        }
        if (in_array($arg, ['pasado', 'anterior', 'mes pasado'], true)) {
            return self::fromStart(self::firstDayOfThisMonth()->modify('first day of last month'));
        }

        $month = $year = null;
        if (preg_match('#^(\d{1,2})[/\-](\d{4})$#', $arg, $m)) {
            $month = (int) $m[1];
            $year = (int) $m[2];
        } elseif (preg_match('#^(\d{4})[/\-](\d{1,2})$#', $arg, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        } else {
            return null;
        }

        if ($month < 1 || $month > 12) {
            return null;
        }

        $start = (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0, 0, 0);

        return self::fromStart($start);
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string}
     */
    private static function fromStart(\DateTimeImmutable $start): array
    {
        return [
            'start' => $start,
            'end' => $start->modify('first day of next month'),
            'label' => self::NAMES[(int) $start->format('n')] . ' ' . $start->format('Y'),
        ];
    }

    private static function firstDayOfThisMonth(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('today'))->modify('first day of this month')->setTime(0, 0, 0);
    }
}

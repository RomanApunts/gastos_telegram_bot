<?php

namespace App\Telegram\Command;

use App\Entity\RecurringIncome;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Da de alta un ingreso fijo mensual:  /ingresofijo 1 1800 Nómina
 * Formato: /ingresofijo <día> <importe> [descripción]
 */
final class AddRecurringIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function names(): array
    {
        return ['ingresofijo', 'ingfijo'];
    }

    public function help(): string
    {
        return '/ingresofijo <día> <importe> [descripción] — ingreso fijo mensual'
            . ' (día negativo = desde el final: -1 último día, -2 penúltimo)';
    }

    public function handle(BotContext $ctx): string
    {
        $parts = preg_split('/\s+/', trim($ctx->args), 3);
        if (count($parts) < 2) {
            return "Uso: /ingresofijo <día> <importe> [descripción]\n"
                . "Ej: /ingresofijo 1 1800 Nómina  ·  /ingresofijo -1 1200 Alquiler (último día)";
        }

        if (!preg_match('/^-?\d+$/', $parts[0]) || !$this->isValidDay((int) $parts[0])) {
            return "❌ El día debe ir de 1 a 31, o de -1 a -28 para contar desde el final"
                . " (-1 = último día). Recibí «{$parts[0]}».";
        }
        $day = (int) $parts[0];

        $amount = Money::parse($parts[1]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$parts[1]}». Ejemplo: 1800";
        }

        $description = trim($parts[2] ?? '');
        $recurring = new RecurringIncome(
            $ctx->user,
            $amount,
            $day,
            $description === '' ? null : $description,
        );
        $this->em->persist($recurring);
        $this->em->flush();

        $desc = $description !== '' ? " ({$description})" : '';

        return "🔁 Ingreso fijo creado: " . Money::format($amount) . $desc
            . ", " . $recurring->getDayLabel() . ".";
    }

    /** Acepta 1..31 (día fijo) o -1..-28 (desde el final, válido en cualquier mes). */
    private function isValidDay(int $day): bool
    {
        return ($day >= 1 && $day <= 31) || ($day <= -1 && $day >= -28);
    }
}

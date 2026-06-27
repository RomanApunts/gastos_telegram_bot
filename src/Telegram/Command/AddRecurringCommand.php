<?php

namespace App\Telegram\Command;

use App\Entity\RecurringExpense;
use App\Telegram\BotContext;
use App\Telegram\CategoryMatcher;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Da de alta un gasto fijo mensual:  /recurrente 1 800 Alquiler
 * Formato: /recurrente <día> <importe> <categoría> [descripción]
 */
final class AddRecurringCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryMatcher $matcher,
    ) {
    }

    public function names(): array
    {
        return ['recurrente', 'fijo'];
    }

    public function help(): string
    {
        return '/recurrente <día> <importe> <categoría> [descripción] — gasto fijo mensual'
            . ' (día negativo = desde el final: -1 último día, -2 penúltimo)';
    }

    public function handle(BotContext $ctx): string
    {
        $parts = preg_split('/\s+/', trim($ctx->args), 3);
        if (count($parts) < 3) {
            return "Uso: /recurrente <día> <importe> <categoría> [descripción]\n"
                . "Ej: /recurrente 1 800 Alquiler  ·  /recurrente -1 1200 Nómina (último día)";
        }

        if (!preg_match('/^-?\d+$/', $parts[0]) || !$this->isValidDay((int) $parts[0])) {
            return "❌ El día debe ir de 1 a 31, o de -1 a -28 para contar desde el final"
                . " (-1 = último día). Recibí «{$parts[0]}».";
        }
        $day = (int) $parts[0];

        $amount = Money::parse($parts[1]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$parts[1]}». Ejemplo: 800";
        }

        [$category, $description] = $this->matcher->match($parts[2]);
        if ($category === null) {
            return "❌ No encuentro esa categoría en «{$parts[2]}».";
        }

        $recurring = new RecurringExpense($category, $ctx->user, $amount, $day, $description);
        $this->em->persist($recurring);
        $this->em->flush();

        $desc = $description !== null ? " ({$description})" : '';

        return "🔁 Gasto fijo creado: " . Money::format($amount) . " en {$category->getName()}{$desc}"
            . ", " . $recurring->getDayLabel() . ".";
    }

    /** Acepta 1..31 (día fijo) o -1..-28 (desde el final, válido en cualquier mes). */
    private function isValidDay(int $day): bool
    {
        return ($day >= 1 && $day <= 31) || ($day <= -1 && $day >= -28);
    }
}

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
        return '/recurrente <día> <importe> <categoría> [descripción] — gasto fijo mensual';
    }

    public function handle(BotContext $ctx): string
    {
        $parts = preg_split('/\s+/', trim($ctx->args), 3);
        if (count($parts) < 3) {
            return "Uso: /recurrente <día> <importe> <categoría> [descripción]\nEj: /recurrente 1 800 Alquiler";
        }

        if (!ctype_digit($parts[0]) || (int) $parts[0] < 1 || (int) $parts[0] > 31) {
            return "❌ El día debe ser un número entre 1 y 31. Recibí «{$parts[0]}».";
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
            . ", cada día {$day} del mes.";
    }
}

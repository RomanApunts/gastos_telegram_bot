<?php

namespace App\Telegram\Command;

use App\Entity\RecurringIncome;
use App\Repository\RecurringIncomeRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;

/**
 * Lista los ingresos fijos:  /ingresosfijos
 */
final class ListRecurringIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly RecurringIncomeRepository $recurring,
    ) {
    }

    public function names(): array
    {
        return ['ingresosfijos', 'ingfijos'];
    }

    public function help(): string
    {
        return '/ingresosfijos — lista los ingresos fijos mensuales';
    }

    public function handle(BotContext $ctx): string
    {
        $items = $this->recurring->findActiveOrdered();
        if ($items === []) {
            return 'No hay ingresos fijos. Crea uno con /ingresofijo <día> <importe> [descripción].';
        }

        $lines = array_map(fn (RecurringIncome $r) => $this->line($r), $items);

        return "🔁 Ingresos fijos mensuales:\n" . implode("\n", $lines);
    }

    private function line(RecurringIncome $r): string
    {
        $desc = $r->getDescription() !== null ? " {$r->getDescription()}" : '';

        return "#{$r->getId()} · {$r->getDayLabel()} · " . Money::format($r->getAmount()) . "{$desc}";
    }
}

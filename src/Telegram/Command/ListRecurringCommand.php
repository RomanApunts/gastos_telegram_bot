<?php

namespace App\Telegram\Command;

use App\Entity\RecurringExpense;
use App\Repository\RecurringExpenseRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;

/**
 * Lista los gastos fijos:  /recurrentes
 */
final class ListRecurringCommand implements BotCommandInterface
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurring,
    ) {
    }

    public function names(): array
    {
        return ['recurrentes', 'fijos'];
    }

    public function help(): string
    {
        return '/recurrentes — lista los gastos fijos mensuales';
    }

    public function handle(BotContext $ctx): string
    {
        $items = $this->recurring->findActiveOrdered();
        if ($items === []) {
            return 'No hay gastos fijos. Crea uno con /recurrente <día> <importe> <categoría>.';
        }

        $lines = array_map(fn (RecurringExpense $r) => $this->line($r), $items);

        return "🔁 Gastos fijos mensuales:\n" . implode("\n", $lines);
    }

    private function line(RecurringExpense $r): string
    {
        $desc = $r->getDescription() !== null ? " {$r->getDescription()}" : '';

        return "#{$r->getId()} · día {$r->getDayOfMonth()} · {$r->getCategory()->getName()}"
            . " · " . Money::format($r->getAmount()) . "{$desc}";
    }
}

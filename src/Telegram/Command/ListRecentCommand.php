<?php

namespace App\Telegram\Command;

use App\Entity\Expense;
use App\Repository\ExpenseRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;

/**
 * Últimos gastos con su ID:  /ultimos [n]
 */
final class ListRecentCommand implements BotCommandInterface
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
    ) {
    }

    public function names(): array
    {
        return ['ultimos', 'últimos', 'lista'];
    }

    public function help(): string
    {
        return '/ultimos [n] — últimos gastos con su ID (para editar/borrar)';
    }

    public function handle(BotContext $ctx): string
    {
        $n = (int) trim($ctx->args);
        $n = $n < 1 ? 10 : min($n, 30);

        $expenses = $this->expenses->findRecent($n);
        if ($expenses === []) {
            return 'No hay gastos registrados todavía.';
        }

        $lines = array_map(fn (Expense $e) => $this->line($e), $expenses);

        return "🧾 Últimos gastos:\n" . implode("\n", $lines);
    }

    private function line(Expense $e): string
    {
        $desc = $e->getDescription() !== null ? " {$e->getDescription()}" : '';

        return "#{$e->getId()} · {$e->getSpentAt()->format('d/m')} · {$e->getCategory()->getName()}"
            . " · " . Money::format($e->getAmount()) . "{$desc} — {$e->getUser()->getName()}";
    }
}

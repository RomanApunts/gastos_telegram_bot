<?php

namespace App\Telegram\Command;

use App\Entity\Income;
use App\Repository\IncomeRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;

/**
 * Últimos ingresos:  /ingresos [n]
 */
final class ListIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly IncomeRepository $incomes,
    ) {
    }

    public function names(): array
    {
        return ['ingresos'];
    }

    public function help(): string
    {
        return '/ingresos [n] — últimos ingresos con su ID';
    }

    public function handle(BotContext $ctx): string
    {
        $n = (int) trim($ctx->args);
        $n = $n < 1 ? 10 : min($n, 30);

        $incomes = $this->incomes->findRecent($n);
        if ($incomes === []) {
            return 'No hay ingresos registrados todavía.';
        }

        $lines = array_map(fn (Income $i) => $this->line($i), $incomes);

        return "💰 Últimos ingresos:\n" . implode("\n", $lines);
    }

    private function line(Income $i): string
    {
        $desc = $i->getDescription() !== null ? " {$i->getDescription()}" : '';

        return "#{$i->getId()} · {$i->getReceivedAt()->format('d/m')} · "
            . Money::format($i->getAmount()) . "{$desc} — {$i->getUser()->getName()}";
    }
}

<?php

namespace App\Service;

use App\Repository\CategoryBudgetRepository;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use App\Repository\IncomeRepository;
use App\Telegram\Util\Money;

/**
 * Construye el texto del resumen mensual. Lo usan el comando /resumen del bot
 * y el envío automático programado (app:summary:send).
 */
final class SummaryBuilder
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryBudgetRepository $budgets,
        private readonly IncomeRepository $incomes,
    ) {
    }

    /**
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     */
    public function build(array $period): string
    {
        $spentMap = $this->expenses->sumByCategoryForPeriod($period['start'], $period['end']);
        $limitMap = $this->budgets->findEffectiveAmountsForMonth($period['start']);

        $lines = [];
        $totalSpent = '0';
        $totalLimit = '0';

        foreach ($this->categories->findActiveOrdered() as $cat) {
            $spent = $spentMap[$cat->getId()] ?? '0';
            $limit = $limitMap[$cat->getId()] ?? null;

            // Omitimos categorías sin gasto ni límite para no hacer ruido.
            if ($limit === null && bccomp($spent, '0', 2) === 0) {
                continue;
            }

            $totalSpent = bcadd($totalSpent, $spent, 2);

            if ($limit !== null) {
                $totalLimit = bcadd($totalLimit, $limit, 2);
                $lines[] = $this->budgetedLine($cat->getName(), $spent, $limit);
            } else {
                $lines[] = "▪️ {$cat->getName()}: " . Money::format($spent) . " (sin límite)";
            }
        }

        $income = $this->incomes->sumForPeriod($period['start'], $period['end']);
        $hasIncome = bccomp($income, '0', 2) > 0;

        if ($lines === [] && !$hasIncome) {
            return "📊 {$period['label']}\n\nSin movimientos registrados.";
        }

        $msg = "📊 Resumen de {$period['label']}\n\n"
            . ($lines !== [] ? implode("\n", $lines) : 'Sin gastos registrados.');

        $msg .= "\n\n💸 Gastos: " . Money::format($totalSpent);
        if (bccomp($totalLimit, '0', 2) > 0) {
            $msg .= " de " . Money::format($totalLimit);
        }

        if ($hasIncome) {
            $balance = bcsub($income, $totalSpent, 2);
            $msg .= "\n💰 Ingresos: " . Money::format($income);
            $msg .= "\n⚖️ Balance: " . Money::format($balance)
                . (bccomp($balance, '0', 2) >= 0 ? ' ✅' : ' 🔴');
        }

        return $msg;
    }

    private function budgetedLine(string $name, string $spent, string $limit): string
    {
        $pct = (int) round(((float) $spent / (float) $limit) * 100);
        $remaining = bcsub($limit, $spent, 2);

        $emoji = $pct < 80 ? '🟢' : ($pct < 100 ? '🟡' : '🔴');

        $line = "{$emoji} {$name}: " . Money::format($spent) . " / " . Money::format($limit) . " ({$pct}%)";
        if (bccomp($remaining, '0', 2) >= 0) {
            $line .= " · quedan " . Money::format($remaining);
        } else {
            $line .= " · pasado " . Money::format(ltrim($remaining, '-'));
        }

        return $line;
    }
}

<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Repository\CategoryBudgetRepository;
use App\Repository\CategoryRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use App\Telegram\Util\Months;

/**
 * Muestra los límites vigentes del mes:  /limites
 */
final class ListBudgetsCommand implements BotCommandInterface
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly CategoryBudgetRepository $budgets,
    ) {
    }

    public function names(): array
    {
        return ['limites', 'límites', 'presupuestos'];
    }

    public function help(): string
    {
        return '/limites — muestra los máximos mensuales vigentes';
    }

    public function handle(BotContext $ctx): string
    {
        $cats = $this->categories->findActiveOrdered();
        if ($cats === []) {
            return 'No hay categorías todavía.';
        }

        $period = Months::current();
        $amounts = $this->budgets->findEffectiveAmountsForMonth($period['start']);

        $lines = [];
        foreach ($cats as $cat) {
            $limit = $amounts[$cat->getId()] ?? null;
            $lines[] = $limit !== null
                ? "• {$cat->getName()}: " . Money::format($limit)
                : "• {$cat->getName()}: sin límite";
        }

        return "🎯 Límites de {$period['label']}:\n" . implode("\n", $lines);
    }
}

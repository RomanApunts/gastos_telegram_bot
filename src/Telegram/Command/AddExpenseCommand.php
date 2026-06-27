<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Entity\Expense;
use App\Repository\CategoryBudgetRepository;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use App\Service\Notifier;
use App\Telegram\BotContext;
use App\Telegram\CategoryMatcher;
use App\Telegram\Util\Money;
use App\Telegram\Util\Months;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Registra un gasto:  /gasto 12,50 Comida menú del día
 */
final class AddExpenseCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categories,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryBudgetRepository $budgets,
        private readonly CategoryMatcher $matcher,
        private readonly Notifier $notifier,
    ) {
    }

    public function names(): array
    {
        return ['gasto', 'g'];
    }

    public function help(): string
    {
        return '/gasto <importe> <categoría> [descripción] — registra un gasto';
    }

    public function handle(BotContext $ctx): string
    {
        if ($ctx->args === '') {
            return "Uso: /gasto <importe> <categoría> [descripción]\nEj: /gasto 12,50 Comida menú del día";
        }

        // El importe es el primer token; el resto es "categoría [descripción]".
        $parts = preg_split('/\s+/', $ctx->args, 2);
        $amount = Money::parse($parts[0]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$parts[0]}». Ejemplo: 12,50";
        }

        $rest = trim($parts[1] ?? '');
        if ($rest === '') {
            return '❌ Falta la categoría. ' . $this->categoriesHint();
        }

        [$category, $description] = $this->matcher->match($rest);
        if ($category === null) {
            return "❌ No encuentro esa categoría. " . $this->categoriesHint();
        }

        $expense = new Expense($category, $ctx->user, $amount, new \DateTimeImmutable('today'), $description);
        $this->em->persist($expense);
        $this->em->flush();

        return $this->confirmation($ctx, $category, $amount, $description);
    }

    private function confirmation(BotContext $ctx, Category $category, string $amount, ?string $description): string
    {
        $period = Months::current();
        $totals = $this->expenses->sumByCategoryForPeriod($period['start'], $period['end']);
        $spent = $totals[$category->getId()] ?? '0';

        $desc = $description !== null ? " ({$description})" : '';
        $msg = "✅ Registrado: " . Money::format($amount) . " en {$category->getName()}{$desc}\n";
        $msg .= "Este mes llevas " . Money::format($spent) . " en {$category->getName()}";

        $budget = $this->budgets->findEffectiveForMonth($category, $period['start']);
        if ($budget !== null) {
            $remaining = bcsub($budget->getAmount(), $spent, 2);
            if (bccomp($remaining, '0', 2) >= 0) {
                $msg .= " · quedan " . Money::format($remaining) . " del límite";
            } else {
                $msg .= " · ⚠️ te has pasado " . Money::format(ltrim($remaining, '-')) . " del límite";
            }

            $this->maybeAlert($ctx, $category, $budget->getAmount(), $spent, $amount);
        }

        return $msg;
    }

    /**
     * Si este gasto cruza el 80% o el 100% del límite, avisa al resto de usuarios.
     */
    private function maybeAlert(BotContext $ctx, Category $category, string $limit, string $spent, string $amount): void
    {
        $previous = bcsub($spent, $amount, 2);
        $threshold80 = bcmul($limit, '0.8', 2);

        $name = $category->getName();
        $alert = null;

        if (bccomp($previous, $limit, 2) < 0 && bccomp($spent, $limit, 2) >= 0) {
            $over = ltrim(bcsub($spent, $limit, 2), '-');
            $alert = "🔴 Límite superado en {$name}: " . Money::format($spent)
                . " de " . Money::format($limit) . " (pasado " . Money::format($over) . ").";
        } elseif (bccomp($previous, $threshold80, 2) < 0 && bccomp($spent, $threshold80, 2) >= 0) {
            $pct = (int) round(((float) $spent / (float) $limit) * 100);
            $alert = "🟡 Aviso: {$name} va por el {$pct}% del límite (" . Money::format($spent)
                . " de " . Money::format($limit) . ").";
        }

        if ($alert !== null) {
            // El que registra ya lo ve en su confirmación; avisamos al resto.
            $this->notifier->broadcast($alert, $ctx->user->getTelegramId());
        }
    }

    private function categoriesHint(): string
    {
        $cats = $this->categories->findActiveOrdered();
        if ($cats === []) {
            return 'Aún no hay categorías. Crea una con /nuevacategoria <nombre>.';
        }
        $names = implode(', ', array_map(fn (Category $c) => $c->getName(), $cats));

        return "Categorías: {$names}.";
    }
}

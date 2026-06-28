<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Entity\Expense;
use App\Repository\CategoryBudgetRepository;
use App\Repository\ExpenseRepository;
use App\Service\Notifier;
use App\Telegram\BotContext;
use App\Telegram\CategoryMatcher;
use App\Telegram\Receipt\PendingExpensePresenter;
use App\Telegram\Util\Dates;
use App\Telegram\Util\Money;
use App\Telegram\Util\Months;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Registra un gasto:  /gasto 12,50 Comida menú del día
 *
 * - Admite fecha pasada tras la categoría:  /gasto 20 Comida ayer  ·  /gasto 20 Comida 12/06
 * - Si no se reconoce la categoría (o falta), propone el gasto con botones de categoría.
 */
final class AddExpenseCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryBudgetRepository $budgets,
        private readonly CategoryMatcher $matcher,
        private readonly Notifier $notifier,
        private readonly PendingExpensePresenter $presenter,
    ) {
    }

    public function names(): array
    {
        return ['gasto', 'g'];
    }

    public function help(): string
    {
        return '/gasto <importe> <categoría> [fecha] [descripción] — registra un gasto';
    }

    public function handle(BotContext $ctx): string
    {
        if ($ctx->args === '') {
            return "Uso: /gasto <importe> <categoría> [descripción]\nEj: /gasto 12,50 Comida menú del día";
        }

        $parts = preg_split('/\s+/', $ctx->args, 2);
        $amount = Money::parse($parts[0]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$parts[0]}». Ejemplo: 12,50";
        }

        $rest = trim($parts[1] ?? '');

        // Sin texto de categoría → proponer con botones para elegirla.
        if ($rest === '') {
            $this->presenter->propose($ctx->user, $ctx->chatId, $amount, new \DateTimeImmutable('today'), null, null);

            return '';
        }

        [$category, $description] = $this->matcher->match($rest);

        // Categoría no reconocida → proponer con botones; el texto queda como nota.
        if ($category === null) {
            $this->presenter->propose($ctx->user, $ctx->chatId, $amount, new \DateTimeImmutable('today'), null, $rest);

            return '';
        }

        // Fecha opcional al inicio de la descripción (ayer, 12/06…).
        $spentAt = new \DateTimeImmutable('today');
        if ($description !== null) {
            $tokens = preg_split('/\s+/', $description, 2);
            $date = Dates::parse($tokens[0]);
            if ($date !== null) {
                $spentAt = $date;
                $description = trim($tokens[1] ?? '');
                $description = $description === '' ? null : $description;
            }
        }

        $expense = new Expense($category, $ctx->user, $amount, $spentAt, $description);
        $this->em->persist($expense);
        $this->em->flush();

        return $this->confirmation($ctx, $category, $amount, $description, $spentAt);
    }

    private function confirmation(
        BotContext $ctx,
        Category $category,
        string $amount,
        ?string $description,
        \DateTimeImmutable $spentAt,
    ): string {
        $today = new \DateTimeImmutable('today');
        $monthStart = $spentAt->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('first day of next month');
        $isCurrentMonth = $monthStart->format('Y-m') === $today->format('Y-m');

        $totals = $this->expenses->sumByCategoryForPeriod($monthStart, $monthEnd);
        $spent = $totals[$category->getId()] ?? '0';

        $desc = $description !== null ? " ({$description})" : '';
        $date = $spentAt->format('Y-m-d') !== $today->format('Y-m-d') ? ' · ' . $spentAt->format('d/m/Y') : '';
        $msg = "✅ Registrado: " . Money::format($amount) . " en {$category->getName()}{$desc}{$date}\n";

        $when = $isCurrentMonth ? 'Este mes' : 'En ' . Months::labelFor($monthStart);
        $msg .= "{$when} llevas " . Money::format($spent) . " en {$category->getName()}";

        $budget = $this->budgets->findEffectiveForMonth($category, $monthStart);
        if ($budget !== null) {
            $remaining = bcsub($budget->getAmount(), $spent, 2);
            if (bccomp($remaining, '0', 2) >= 0) {
                $msg .= " · quedan " . Money::format($remaining) . " del límite";
            } else {
                $msg .= " · ⚠️ te has pasado " . Money::format(ltrim($remaining, '-')) . " del límite";
            }

            // Solo avisamos al resto si el gasto es del mes en curso.
            if ($isCurrentMonth) {
                $this->maybeAlert($ctx, $category, $budget->getAmount(), $spent, $amount);
            }
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
            $this->notifier->broadcast($alert, $ctx->user->getTelegramId());
        }
    }
}

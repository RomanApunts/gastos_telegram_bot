<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Entity\CategoryBudget;
use App\Repository\CategoryBudgetRepository;
use App\Repository\CategoryRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use App\Telegram\Util\Months;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Fija el límite mensual de una categoría:  /limite Comida 300
 *
 * Aplica desde el mes en curso en adelante. Si ya había un límite definido
 * este mismo mes lo actualiza; los meses anteriores conservan su histórico.
 */
final class SetBudgetCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categories,
        private readonly CategoryBudgetRepository $budgets,
    ) {
    }

    public function names(): array
    {
        return ['limite', 'límite', 'presupuesto'];
    }

    public function help(): string
    {
        return '/limite <categoría> <importe> — fija el máximo mensual de una categoría';
    }

    public function handle(BotContext $ctx): string
    {
        if ($ctx->args === '') {
            return "Uso: /limite <categoría> <importe>\nEj: /limite Comida 300";
        }

        // El importe es el último token; lo anterior es el nombre de la categoría.
        if (!preg_match('/^(.*\S)\s+(\S+)$/u', trim($ctx->args), $m)) {
            return 'Uso: /limite <categoría> <importe>';
        }
        $categoryName = trim($m[1]);
        $amount = Money::parse($m[2]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$m[2]}». Ejemplo: 300";
        }

        $category = $this->findCategory($categoryName);
        if ($category === null) {
            return "❌ No encuentro la categoría «{$categoryName}».";
        }

        $period = Months::current();
        $existing = $this->budgets->findOneBy([
            'category' => $category,
            'effectiveFrom' => $period['start'],
        ]);

        if ($existing !== null) {
            $existing->setAmount($amount);
        } else {
            $this->em->persist(new CategoryBudget($category, $amount, $period['start']));
        }
        $this->em->flush();

        return "✅ Límite de {$category->getName()}: " . Money::format($amount)
            . " al mes (desde {$period['label']}).";
    }

    private function findCategory(string $name): ?Category
    {
        foreach ($this->categories->findBy([]) as $cat) {
            if (mb_strtolower($cat->getName()) === mb_strtolower($name)) {
                return $cat;
            }
        }

        return null;
    }
}

<?php

namespace App\Telegram\Command;

use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Corrige un gasto:  /editar 42 importe 18,90   ·   /editar 42 categoria Ocio
 */
final class EditExpenseCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function names(): array
    {
        return ['editar', 'edit'];
    }

    public function help(): string
    {
        return '/editar <id> <importe|categoria|descripcion> <valor> — corrige un gasto';
    }

    public function handle(BotContext $ctx): string
    {
        $parts = preg_split('/\s+/', trim($ctx->args), 3);
        if (count($parts) < 2 || !ctype_digit($parts[0])) {
            return "Uso: /editar <id> <importe|categoria|descripcion> <valor>\nEj: /editar 42 importe 18,90";
        }

        $expense = $this->expenses->find((int) $parts[0]);
        if ($expense === null) {
            return "❌ No existe ningún gasto con ID #{$parts[0]}.";
        }

        $field = mb_strtolower($parts[1]);
        $value = trim($parts[2] ?? '');

        switch ($field) {
            case 'importe':
            case 'cantidad':
            case 'coste':
                $amount = Money::parse($value);
                if ($amount === null) {
                    return "❌ Importe no válido: «{$value}».";
                }
                $expense->setAmount($amount);
                $changed = 'importe → ' . Money::format($amount);
                break;

            case 'categoria':
            case 'categoría':
                $category = $this->categories->findOneByNameInsensitive($value);
                if ($category === null) {
                    return "❌ No encuentro la categoría «{$value}».";
                }
                $expense->setCategory($category);
                $changed = 'categoría → ' . $category->getName();
                break;

            case 'descripcion':
            case 'descripción':
            case 'desc':
                $expense->setDescription($value === '' ? null : $value);
                $changed = 'descripción → ' . ($value === '' ? '(vacía)' : $value);
                break;

            default:
                return 'Campo no reconocido. Usa: importe, categoria o descripcion.';
        }

        $this->em->flush();

        return "✏️ Gasto #{$parts[0]} actualizado: {$changed}.";
    }
}

<?php

namespace App\Telegram\Command;

use App\Repository\ExpenseRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Borra un gasto por ID:  /borrar 42
 */
final class DeleteExpenseCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExpenseRepository $expenses,
    ) {
    }

    public function names(): array
    {
        return ['borrar', 'eliminar', 'del'];
    }

    public function help(): string
    {
        return '/borrar <id> — elimina un gasto (mira el ID con /ultimos)';
    }

    public function handle(BotContext $ctx): string
    {
        $id = trim($ctx->args);
        if (!ctype_digit($id)) {
            return 'Uso: /borrar <id>. Consulta el ID con /ultimos.';
        }

        $expense = $this->expenses->find((int) $id);
        if ($expense === null) {
            return "❌ No existe ningún gasto con ID #{$id}.";
        }

        $resumen = Money::format($expense->getAmount()) . " en {$expense->getCategory()->getName()}";
        $this->em->remove($expense);
        $this->em->flush();

        return "🗑️ Borrado #{$id}: {$resumen}.";
    }
}

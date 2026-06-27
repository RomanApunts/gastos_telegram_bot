<?php

namespace App\Telegram\Command;

use App\Repository\RecurringExpenseRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Elimina un gasto fijo:  /borrarrecurrente 3
 */
final class DeleteRecurringCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RecurringExpenseRepository $recurring,
    ) {
    }

    public function names(): array
    {
        return ['borrarrecurrente', 'borrarfijo'];
    }

    public function help(): string
    {
        return '/borrarrecurrente <id> — elimina un gasto fijo (ID con /recurrentes)';
    }

    public function handle(BotContext $ctx): string
    {
        $id = trim($ctx->args);
        if (!ctype_digit($id)) {
            return 'Uso: /borrarrecurrente <id>. Consulta el ID con /recurrentes.';
        }

        $item = $this->recurring->find((int) $id);
        if ($item === null) {
            return "❌ No existe ningún gasto fijo con ID #{$id}.";
        }

        $resumen = Money::format($item->getAmount()) . " en {$item->getCategory()->getName()}";
        $this->em->remove($item);
        $this->em->flush();

        return "🗑️ Gasto fijo #{$id} eliminado: {$resumen}.";
    }
}

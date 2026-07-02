<?php

namespace App\Telegram\Command;

use App\Repository\RecurringIncomeRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Elimina un ingreso fijo:  /borraringresofijo 3
 */
final class DeleteRecurringIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RecurringIncomeRepository $recurring,
    ) {
    }

    public function names(): array
    {
        return ['borraringresofijo', 'borraringfijo'];
    }

    public function help(): string
    {
        return '/borraringresofijo <id> — elimina un ingreso fijo (ID con /ingresosfijos)';
    }

    public function handle(BotContext $ctx): string
    {
        $id = trim($ctx->args);
        if (!ctype_digit($id)) {
            return 'Uso: /borraringresofijo <id>. Consulta el ID con /ingresosfijos.';
        }

        $item = $this->recurring->find((int) $id);
        if ($item === null) {
            return "❌ No existe ningún ingreso fijo con ID #{$id}.";
        }

        $resumen = Money::format($item->getAmount());
        $this->em->remove($item);
        $this->em->flush();

        return "🗑️ Ingreso fijo #{$id} eliminado: {$resumen}.";
    }
}

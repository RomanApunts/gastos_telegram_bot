<?php

namespace App\Telegram\Command;

use App\Repository\IncomeRepository;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Borra un ingreso por ID:  /borraringreso 4
 */
final class DeleteIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IncomeRepository $incomes,
    ) {
    }

    public function names(): array
    {
        return ['borraringreso', 'borrarin'];
    }

    public function help(): string
    {
        return '/borraringreso <id> — elimina un ingreso (ID con /ingresos)';
    }

    public function handle(BotContext $ctx): string
    {
        $id = trim($ctx->args);
        if (!ctype_digit($id)) {
            return 'Uso: /borraringreso <id>. Consulta el ID con /ingresos.';
        }

        $income = $this->incomes->find((int) $id);
        if ($income === null) {
            return "❌ No existe ningún ingreso con ID #{$id}.";
        }

        $amount = Money::format($income->getAmount());
        $this->em->remove($income);
        $this->em->flush();

        return "🗑️ Ingreso #{$id} eliminado: {$amount}.";
    }
}

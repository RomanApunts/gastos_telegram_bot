<?php

namespace App\Telegram\Receipt;

use App\Entity\Category;
use App\Entity\PendingExpense;
use App\Entity\TelegramUser;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crea un gasto pendiente y envía el mensaje de confirmación con botones inline.
 * Lo usan tanto el flujo de tickets como /gasto cuando falta la categoría.
 */
final class PendingExpensePresenter
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function propose(
        TelegramUser $user,
        string $chatId,
        string $amount,
        \DateTimeImmutable $spentAt,
        ?Category $category,
        ?string $description,
    ): void {
        $pending = new PendingExpense($user, $chatId, $amount, $spentAt, $category, $description);
        $this->em->persist($pending);
        $this->em->flush();

        $messageId = $this->api->sendMessageWithKeyboard($chatId, $this->summary($pending), $this->keyboard($pending));
        if ($messageId !== null) {
            $pending->setMessageId($messageId);
            $this->em->flush();
        }
    }

    public function summary(PendingExpense $p): string
    {
        $category = $p->getCategory()?->getName() ?? '— (elige una)';
        $note = $p->getDescription() !== null ? "\n🏪 {$p->getDescription()}" : '';

        return "🧾 Gasto a confirmar\n"
            . '💶 ' . Money::format($p->getAmount()) . "\n"
            . '📅 ' . $p->getSpentAt()->format('d/m/Y')
            . $note . "\n"
            . "🏷️ {$category}";
    }

    /**
     * @return array<int, array<int, array{text: string, callback_data: string}>>
     */
    public function keyboard(PendingExpense $p): array
    {
        $id = $p->getId();
        $topRow = [];
        if ($p->getCategory() !== null) {
            $topRow[] = ['text' => '✅ Confirmar', 'callback_data' => "g:c:{$id}"];
        }
        $topRow[] = ['text' => '🏷️ Categoría', 'callback_data' => "g:m:{$id}"];

        return [
            $topRow,
            [['text' => '❌ Cancelar', 'callback_data' => "g:x:{$id}"]],
        ];
    }
}

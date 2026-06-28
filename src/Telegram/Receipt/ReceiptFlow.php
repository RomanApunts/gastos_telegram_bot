<?php

namespace App\Telegram\Receipt;

use App\Entity\Category;
use App\Entity\PendingExpense;
use App\Entity\TelegramUser;
use App\Repository\CategoryRepository;
use App\Service\Receipt\ReceiptExtractorInterface;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Procesa la foto de un ticket: la lee, crea un gasto pendiente y envía el
 * mensaje de confirmación con botones inline.
 */
final class ReceiptFlow
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly ReceiptExtractorInterface $extractor,
        private readonly CategoryRepository $categories,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handlePhoto(TelegramUser $user, string $chatId, string $fileId): void
    {
        $this->api->sendMessage($chatId, '🔎 Leyendo el ticket…');

        $path = $this->api->getFilePath($fileId);
        $bytes = $path !== null ? $this->api->downloadFile($path) : null;
        if ($bytes === null) {
            $this->api->sendMessage($chatId, '❌ No pude descargar la imagen. Inténtalo de nuevo.');

            return;
        }

        $names = array_map(fn (Category $c) => $c->getName(), $this->categories->findActiveOrdered());

        try {
            $receipt = $this->extractor->extract($bytes, $this->mimeFromPath($path), $names);
        } catch (\Throwable $e) {
            $this->logger->error('Extracción de ticket falló: ' . $e->getMessage());
            $this->api->sendMessage($chatId, '❌ No pude leer el ticket. Prueba con otra foto o usa /gasto.');

            return;
        }

        if (!$receipt->hasAmount()) {
            $this->api->sendMessage($chatId, '❓ No encontré el importe en el ticket. Regístralo con /gasto.');

            return;
        }

        $category = $receipt->category !== null
            ? $this->categories->findOneByNameInsensitive($receipt->category)
            : null;
        $spentAt = $receipt->date ?? new \DateTimeImmutable('today');

        $pending = new PendingExpense($user, $chatId, $receipt->amount, $spentAt, $category, $receipt->merchant);
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
        $merchant = $p->getDescription() !== null ? "\n🏪 {$p->getDescription()}" : '';

        return "🧾 Ticket detectado\n"
            . '💶 ' . Money::format($p->getAmount()) . "\n"
            . '📅 ' . $p->getSpentAt()->format('d/m/Y')
            . $merchant . "\n"
            . "🏷️ {$category}";
    }

    /**
     * Teclado principal: Confirmar (si hay categoría), Categoría, Cancelar.
     *
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

    private function mimeFromPath(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}

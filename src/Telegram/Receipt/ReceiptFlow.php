<?php

namespace App\Telegram\Receipt;

use App\Entity\Category;
use App\Entity\TelegramUser;
use App\Repository\CategoryRepository;
use App\Service\Receipt\ReceiptExtractorInterface;
use App\Service\Telegram\TelegramApi;
use Psr\Log\LoggerInterface;

/**
 * Procesa la foto de un ticket: la lee y propone un gasto pendiente con botones.
 */
final class ReceiptFlow
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly ReceiptExtractorInterface $extractor,
        private readonly CategoryRepository $categories,
        private readonly PendingExpensePresenter $presenter,
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

        $this->presenter->propose($user, $chatId, $receipt->amount, $spentAt, $category, $receipt->merchant);
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

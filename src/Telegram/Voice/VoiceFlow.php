<?php

namespace App\Telegram\Voice;

use App\Entity\Category;
use App\Entity\TelegramUser;
use App\Repository\CategoryRepository;
use App\Service\Voice\VoiceExtractorInterface;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Receipt\PendingExpensePresenter;
use Psr\Log\LoggerInterface;

/**
 * Procesa una nota de voz: la interpreta y propone un gasto o un ingreso
 * pendiente con botones de confirmación (misma filosofía que los tickets).
 */
final class VoiceFlow
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly VoiceExtractorInterface $extractor,
        private readonly CategoryRepository $categories,
        private readonly PendingExpensePresenter $expensePresenter,
        private readonly PendingIncomePresenter $incomePresenter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleVoice(TelegramUser $user, string $chatId, string $fileId, string $mimeType): void
    {
        $this->api->sendMessage($chatId, '🎙️ Escuchando…');

        $path = $this->api->getFilePath($fileId);
        $bytes = $path !== null ? $this->api->downloadFile($path) : null;
        if ($bytes === null) {
            $this->api->sendMessage($chatId, '❌ No pude descargar el audio. Inténtalo de nuevo.');

            return;
        }

        $names = array_map(fn (Category $c) => $c->getName(), $this->categories->findActiveOrdered());
        $today = new \DateTimeImmutable('today');

        try {
            $entry = $this->extractor->extract($bytes, $mimeType, $names, $today);
        } catch (\Throwable $e) {
            $this->logger->error('Interpretación de voz falló: ' . $e->getMessage());
            $this->api->sendMessage($chatId, '❌ No pude interpretar el audio. Prueba de nuevo o usa /gasto o /ingreso.');

            return;
        }

        if (!$entry->hasAmount()) {
            $heard = $entry->transcript !== null ? "\n\n🎙️ Entendí: «{$entry->transcript}»" : '';
            $this->api->sendMessage(
                $chatId,
                '❓ No encontré el importe en el audio. Regístralo con /gasto o /ingreso.' . $heard,
            );

            return;
        }

        $date = $entry->date ?? $today;

        if ($entry->isIncome()) {
            $this->incomePresenter->propose($user, $chatId, $entry->amount, $date, $entry->description, $entry->transcript);

            return;
        }

        $category = $entry->category !== null
            ? $this->categories->findOneByNameInsensitive($entry->category)
            : null;

        $this->expensePresenter->propose($user, $chatId, $entry->amount, $date, $category, $entry->description);
    }
}

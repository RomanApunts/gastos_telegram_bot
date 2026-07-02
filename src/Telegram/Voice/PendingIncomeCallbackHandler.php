<?php

namespace App\Telegram\Voice;

use App\Entity\Income;
use App\Entity\TelegramUser;
use App\Repository\PendingIncomeRepository;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Procesa las pulsaciones de los botones del ingreso por voz.
 * callback_data: pi:<acción>:<pendingId>
 *   c = confirmar · x = cancelar
 */
final class PendingIncomeCallbackHandler
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly PendingIncomeRepository $pendings,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function handle(TelegramUser $user, string $callbackId, string $chatId, int $messageId, string $data): void
    {
        $this->api->answerCallbackQuery($callbackId);

        $parts = explode(':', $data);
        $action = $parts[1] ?? '';
        $pending = $this->pendings->find((int) ($parts[2] ?? 0));

        if ($pending === null) {
            $this->api->editMessageText($chatId, $messageId, 'Este ingreso ya no está disponible.');

            return;
        }

        if ($action === 'x') {
            $this->em->remove($pending);
            $this->em->flush();
            $this->api->editMessageText($chatId, $messageId, '❌ Ingreso descartado.');

            return;
        }

        if ($action === 'c') {
            $income = new Income(
                $user,
                $pending->getAmount(),
                $pending->getReceivedAt(),
                $pending->getDescription(),
            );
            $this->em->persist($income);
            $this->em->remove($pending);
            $this->em->flush();

            $desc = $income->getDescription() !== null ? " ({$income->getDescription()})" : '';
            $this->api->editMessageText(
                $chatId,
                $messageId,
                '✅ Ingreso registrado: ' . Money::format($income->getAmount()) . $desc,
            );
        }
    }
}

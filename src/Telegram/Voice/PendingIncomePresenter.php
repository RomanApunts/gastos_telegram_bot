<?php

namespace App\Telegram\Voice;

use App\Entity\PendingIncome;
use App\Entity\TelegramUser;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crea un ingreso pendiente y envía el mensaje de confirmación con botones inline.
 * Lo usa el flujo de notas de voz cuando el movimiento es un ingreso.
 */
final class PendingIncomePresenter
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
        \DateTimeImmutable $receivedAt,
        ?string $description,
        ?string $transcript = null,
    ): void {
        $pending = new PendingIncome($user, $chatId, $amount, $receivedAt, $description);
        $this->em->persist($pending);
        $this->em->flush();

        $messageId = $this->api->sendMessageWithKeyboard(
            $chatId,
            $this->summary($pending, $transcript),
            $this->keyboard($pending),
        );
        if ($messageId !== null) {
            $pending->setMessageId($messageId);
            $this->em->flush();
        }
    }

    public function summary(PendingIncome $p, ?string $transcript = null): string
    {
        $note = $p->getDescription() !== null ? "\n📝 {$p->getDescription()}" : '';
        $heard = ($transcript !== null && $transcript !== '') ? "🎙️ «{$transcript}»\n\n" : '';

        return $heard
            . "💰 Ingreso a confirmar\n"
            . '💶 ' . Money::format($p->getAmount()) . "\n"
            . '📅 ' . $p->getReceivedAt()->format('d/m/Y')
            . $note;
    }

    /**
     * @return array<int, array<int, array{text: string, callback_data: string}>>
     */
    public function keyboard(PendingIncome $p): array
    {
        $id = $p->getId();

        return [[
            ['text' => '✅ Confirmar', 'callback_data' => "pi:c:{$id}"],
            ['text' => '❌ Cancelar', 'callback_data' => "pi:x:{$id}"],
        ]];
    }
}

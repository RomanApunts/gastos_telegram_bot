<?php

namespace App\Telegram\Receipt;

use App\Entity\Category;
use App\Entity\Expense;
use App\Entity\PendingExpense;
use App\Entity\TelegramUser;
use App\Repository\CategoryRepository;
use App\Repository\PendingExpenseRepository;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Procesa las pulsaciones de los botones del flujo de tickets.
 * callback_data: g:<acción>:<pendingId>[:<categoryId>]
 *   c = confirmar · m = menú categorías · s = fijar categoría · b = volver · x = cancelar
 */
final class PendingExpenseCallbackHandler
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly PendingExpenseRepository $pendings,
        private readonly CategoryRepository $categories,
        private readonly EntityManagerInterface $em,
        private readonly PendingExpensePresenter $presenter,
    ) {
    }

    public function handle(TelegramUser $user, string $callbackId, string $chatId, int $messageId, string $data): void
    {
        $this->api->answerCallbackQuery($callbackId);

        $parts = explode(':', $data);
        $action = $parts[1] ?? '';
        $pending = $this->pendings->find((int) ($parts[2] ?? 0));

        if ($pending === null) {
            $this->api->editMessageText($chatId, $messageId, 'Este ticket ya no está disponible.');

            return;
        }

        switch ($action) {
            case 'x':
                $this->em->remove($pending);
                $this->em->flush();
                $this->api->editMessageText($chatId, $messageId, '❌ Ticket descartado.');
                break;

            case 'm':
                $this->api->editMessageText(
                    $chatId,
                    $messageId,
                    $this->presenter->summary($pending) . "\n\nElige una categoría:",
                    $this->categoryKeyboard($pending),
                );
                break;

            case 's':
                $category = $this->categories->find((int) ($parts[3] ?? 0));
                if ($category !== null) {
                    $pending->setCategory($category);
                    $this->em->flush();
                }
                $this->api->editMessageText($chatId, $messageId, $this->presenter->summary($pending), $this->presenter->keyboard($pending));
                break;

            case 'b':
                $this->api->editMessageText($chatId, $messageId, $this->presenter->summary($pending), $this->presenter->keyboard($pending));
                break;

            case 'c':
                $this->confirm($user, $pending, $chatId, $messageId);
                break;
        }
    }

    private function confirm(TelegramUser $user, PendingExpense $pending, string $chatId, int $messageId): void
    {
        if ($pending->getCategory() === null) {
            $this->api->editMessageText(
                $chatId,
                $messageId,
                $this->presenter->summary($pending) . "\n\n⚠️ Elige una categoría antes de confirmar.",
                $this->presenter->keyboard($pending),
            );

            return;
        }

        $expense = new Expense(
            $pending->getCategory(),
            $user,
            $pending->getAmount(),
            $pending->getSpentAt(),
            $pending->getDescription(),
        );
        $this->em->persist($expense);
        $this->em->remove($pending);
        $this->em->flush();

        $desc = $expense->getDescription() !== null ? " ({$expense->getDescription()})" : '';
        $this->api->editMessageText(
            $chatId,
            $messageId,
            '✅ Registrado: ' . Money::format($expense->getAmount())
                . " en {$expense->getCategory()->getName()}{$desc}",
        );
    }

    /**
     * @return array<int, array<int, array{text: string, callback_data: string}>>
     */
    private function categoryKeyboard(PendingExpense $pending): array
    {
        $rows = [];
        $row = [];
        foreach ($this->categories->findActiveOrdered() as $category) {
            $row[] = [
                'text' => $category->getName(),
                'callback_data' => "g:s:{$pending->getId()}:{$category->getId()}",
            ];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row !== []) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '⬅️ Volver', 'callback_data' => "g:b:{$pending->getId()}"]];

        return $rows;
    }
}

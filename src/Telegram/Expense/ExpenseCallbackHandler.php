<?php

namespace App\Telegram\Expense;

use App\Entity\TelegramUser;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestiona las pulsaciones de los botones de gastos (callback_data "e:...").
 *   e:list            → volver a la lista
 *   e:v:<id>          → ver detalle
 *   e:delq:<id>       → preguntar confirmación de borrado
 *   e:del:<id>        → borrar (confirmado)
 *   e:cat:<id>        → elegir categoría
 *   e:setcat:<id>:<c> → fijar categoría
 */
final class ExpenseCallbackHandler
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryRepository $categories,
        private readonly EntityManagerInterface $em,
        private readonly ExpenseListPresenter $presenter,
    ) {
    }

    public function handle(TelegramUser $user, string $callbackId, string $chatId, int $messageId, string $data): void
    {
        $this->api->answerCallbackQuery($callbackId);

        $parts = explode(':', $data);
        $action = $parts[1] ?? '';

        if ($action === 'list') {
            $this->edit($chatId, $messageId, $this->presenter->listView());

            return;
        }

        $expense = $this->expenses->find((int) ($parts[2] ?? 0));
        if ($expense === null) {
            $this->api->editMessageText(
                $chatId,
                $messageId,
                'Este gasto ya no existe.',
                [[['text' => '⬅️ Volver a la lista', 'callback_data' => 'e:list']]],
            );

            return;
        }

        switch ($action) {
            case 'v':
                $this->edit($chatId, $messageId, $this->presenter->detailView($expense));
                break;

            case 'delq':
                $this->edit($chatId, $messageId, $this->presenter->confirmDeleteView($expense));
                break;

            case 'del':
                $id = $expense->getId();
                $info = Money::format($expense->getAmount()) . " en {$expense->getCategory()->getName()}";
                $this->em->remove($expense);
                $this->em->flush();
                $this->api->editMessageText(
                    $chatId,
                    $messageId,
                    "🗑️ Gasto #{$id} borrado: {$info}.",
                    [[['text' => '⬅️ Volver a la lista', 'callback_data' => 'e:list']]],
                );
                break;

            case 'cat':
                $this->edit($chatId, $messageId, $this->presenter->categoryView($expense));
                break;

            case 'setcat':
                $category = $this->categories->find((int) ($parts[3] ?? 0));
                if ($category !== null) {
                    $expense->setCategory($category);
                    $this->em->flush();
                }
                $this->edit($chatId, $messageId, $this->presenter->detailView($expense));
                break;
        }
    }

    /**
     * @param array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>} $view
     */
    private function edit(string $chatId, int $messageId, array $view): void
    {
        $this->api->editMessageText(
            $chatId,
            $messageId,
            $view['text'],
            $view['keyboard'] === [] ? null : $view['keyboard'],
        );
    }
}

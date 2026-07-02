<?php

namespace App\Telegram\Income;

use App\Entity\TelegramUser;
use App\Repository\IncomeRepository;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestiona las pulsaciones de los botones de ingresos (callback_data "i:...").
 *   i:list       → volver a la lista
 *   i:v:<id>     → ver detalle
 *   i:delq:<id>  → preguntar confirmación de borrado
 *   i:del:<id>   → borrar (confirmado)
 */
final class IncomeCallbackHandler
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly IncomeRepository $incomes,
        private readonly EntityManagerInterface $em,
        private readonly IncomeListPresenter $presenter,
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

        $income = $this->incomes->find((int) ($parts[2] ?? 0));
        if ($income === null) {
            $this->api->editMessageText(
                $chatId,
                $messageId,
                'Este ingreso ya no existe.',
                [[['text' => '⬅️ Volver a la lista', 'callback_data' => 'i:list']]],
            );

            return;
        }

        switch ($action) {
            case 'v':
                $this->edit($chatId, $messageId, $this->presenter->detailView($income));
                break;

            case 'delq':
                $this->edit($chatId, $messageId, $this->presenter->confirmDeleteView($income));
                break;

            case 'del':
                $id = $income->getId();
                $info = Money::format($income->getAmount());
                $this->em->remove($income);
                $this->em->flush();
                $this->api->editMessageText(
                    $chatId,
                    $messageId,
                    "🗑️ Ingreso #{$id} borrado: {$info}.",
                    [[['text' => '⬅️ Volver a la lista', 'callback_data' => 'i:list']]],
                );
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

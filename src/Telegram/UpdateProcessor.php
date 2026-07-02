<?php

namespace App\Telegram;

use App\Repository\TelegramUserRepository;
use App\Service\Telegram\TelegramApi;
use App\Telegram\Expense\ExpenseCallbackHandler;
use App\Telegram\Income\IncomeCallbackHandler;
use App\Telegram\Menu\MenuCallbackHandler;
use App\Telegram\Receipt\PendingExpenseCallbackHandler;
use App\Telegram\Receipt\ReceiptFlow;

/**
 * Recibe un "update" crudo de Telegram, autoriza al usuario y lo despacha
 * (comando de texto, foto de ticket o pulsación de botón).
 */
final class UpdateProcessor
{
    public function __construct(
        private readonly TelegramUserRepository $users,
        private readonly CommandRouter $router,
        private readonly TelegramApi $api,
        private readonly ReceiptFlow $receiptFlow,
        private readonly PendingExpenseCallbackHandler $callbackHandler,
        private readonly MenuCallbackHandler $menuCallbackHandler,
        private readonly ExpenseCallbackHandler $expenseCallbackHandler,
        private readonly IncomeCallbackHandler $incomeCallbackHandler,
    ) {
    }

    /**
     * @param array<string, mixed> $update
     */
    public function process(array $update): void
    {
        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        if ($chatId === '' || $fromId === '') {
            return;
        }

        $user = $this->users->findActiveByTelegramId($fromId);
        if ($user === null) {
            $this->api->sendMessage(
                $chatId,
                "⛔ No estás autorizado para usar este bot.\nTu ID de Telegram es: {$fromId}"
            );

            return;
        }

        // Foto de un ticket.
        if (isset($message['photo']) && is_array($message['photo']) && $message['photo'] !== []) {
            $fileId = $this->largestPhotoId($message['photo']);
            if ($fileId !== null) {
                $this->receiptFlow->handlePhoto($user, $chatId, $fileId);

                return;
            }
        }

        // Comando de texto.
        $text = (string) ($message['text'] ?? '');
        if (trim($text) === '') {
            return;
        }

        $reply = $this->router->dispatch($user, $chatId, $text);
        if ($reply !== '') {
            $this->api->sendMessage($chatId, $reply);
        }
    }

    /**
     * @param array<string, mixed> $callback
     */
    private function handleCallback(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $fromId = (string) ($callback['from']['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);

        $user = $this->users->findActiveByTelegramId($fromId);
        if ($user === null) {
            $this->api->answerCallbackQuery($callbackId, 'No autorizado.');

            return;
        }

        if ($chatId === '' || $messageId === 0) {
            $this->api->answerCallbackQuery($callbackId);

            return;
        }

        if (str_starts_with($data, 'g:')) {
            $this->callbackHandler->handle($user, $callbackId, $chatId, $messageId, $data);
        } elseif (str_starts_with($data, 'm:')) {
            $this->menuCallbackHandler->handle($user, $callbackId, $chatId, $messageId, $data);
        } elseif (str_starts_with($data, 'e:')) {
            $this->expenseCallbackHandler->handle($user, $callbackId, $chatId, $messageId, $data);
        } elseif (str_starts_with($data, 'i:')) {
            $this->incomeCallbackHandler->handle($user, $callbackId, $chatId, $messageId, $data);
        } else {
            $this->api->answerCallbackQuery($callbackId);
        }
    }

    /**
     * Devuelve el file_id de la foto de mayor resolución.
     *
     * @param array<int, array<string, mixed>> $photos
     */
    private function largestPhotoId(array $photos): ?string
    {
        $best = null;
        $bestArea = -1;
        foreach ($photos as $photo) {
            $area = ((int) ($photo['width'] ?? 0)) * ((int) ($photo['height'] ?? 0));
            if ($area >= $bestArea && isset($photo['file_id'])) {
                $bestArea = $area;
                $best = (string) $photo['file_id'];
            }
        }

        return $best;
    }
}

<?php

namespace App\Telegram;

use App\Repository\TelegramUserRepository;
use App\Service\Telegram\TelegramApi;

/**
 * Recibe un "update" crudo de Telegram, autoriza al usuario y responde.
 */
final class UpdateProcessor
{
    public function __construct(
        private readonly TelegramUserRepository $users,
        private readonly CommandRouter $router,
        private readonly TelegramApi $api,
    ) {
    }

    /**
     * @param array<string, mixed> $update
     */
    public function process(array $update): void
    {
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $fromId = (string) ($message['from']['id'] ?? '');
        $text = (string) ($message['text'] ?? '');

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

        if (trim($text) === '') {
            return;
        }

        $reply = $this->router->dispatch($user, $chatId, $text);
        if ($reply !== '') {
            $this->api->sendMessage($chatId, $reply);
        }
    }
}

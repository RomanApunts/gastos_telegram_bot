<?php

namespace App\Service;

use App\Repository\TelegramUserRepository;
use App\Service\Telegram\TelegramApi;

/**
 * Envía un mensaje a todos los usuarios autorizados del bot.
 * Útil para alertas de presupuesto, resúmenes programados y gastos recurrentes.
 */
final class Notifier
{
    public function __construct(
        private readonly TelegramUserRepository $users,
        private readonly TelegramApi $api,
    ) {
    }

    public function broadcast(string $text, ?string $exceptTelegramId = null): void
    {
        foreach ($this->users->findAllActive() as $user) {
            if ($exceptTelegramId !== null && $user->getTelegramId() === $exceptTelegramId) {
                continue;
            }
            $this->api->sendMessage($user->getTelegramId(), $text);
        }
    }
}

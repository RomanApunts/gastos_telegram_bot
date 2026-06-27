<?php

namespace App\Telegram;

use App\Entity\TelegramUser;

/**
 * Datos de una invocación de comando ya parseada.
 */
final class BotContext
{
    public function __construct(
        public readonly TelegramUser $user,
        public readonly string $chatId,
        public readonly string $command,
        /** Texto que sigue al comando, ya recortado. */
        public readonly string $args,
    ) {
    }
}

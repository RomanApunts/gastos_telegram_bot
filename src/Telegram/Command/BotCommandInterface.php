<?php

namespace App\Telegram\Command;

use App\Telegram\BotContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Un comando del bot. Cada implementación se autoregistra vía el tag.
 */
#[AutoconfigureTag('app.bot_command')]
interface BotCommandInterface
{
    /**
     * Nombres (sin '/') que activan el comando. El primero es el canónico.
     *
     * @return string[]
     */
    public function names(): array;

    /** Línea de ayuda mostrada en /ayuda. */
    public function help(): string;

    /** Procesa el comando y devuelve el texto de respuesta. */
    public function handle(BotContext $ctx): string;
}

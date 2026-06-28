<?php

namespace App\Telegram\Command;

use App\Service\Telegram\TelegramApi;
use App\Telegram\BotContext;
use App\Telegram\Menu\Menu;

/**
 * Muestra el menú principal guiado con botones. Reemplaza la antigua ayuda
 * en texto plano. Se activa con /start, /menu o /ayuda.
 */
final class MenuCommand implements BotCommandInterface
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly Menu $menu,
    ) {
    }

    public function names(): array
    {
        return ['menu', 'start', 'ayuda', 'help', 'inicio'];
    }

    public function help(): string
    {
        return '/menu — abre el menú de opciones';
    }

    public function handle(BotContext $ctx): string
    {
        $screen = $this->menu->screen('main');
        $this->api->sendMessageWithKeyboard($ctx->chatId, $screen['text'], $screen['keyboard']);

        return ''; // el menú ya se ha enviado
    }
}

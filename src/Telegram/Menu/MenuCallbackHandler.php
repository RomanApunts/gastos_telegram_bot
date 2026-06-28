<?php

namespace App\Telegram\Menu;

use App\Entity\TelegramUser;
use App\Service\Telegram\TelegramApi;
use App\Telegram\CommandRouter;

/**
 * Procesa las pulsaciones del menú (callback_data "m:...").
 *   m:nav:<pantalla> → edita el mensaje a esa pantalla
 *   m:run:<clave>    → ejecuta el comando equivalente y envía el resultado
 */
final class MenuCallbackHandler
{
    /** Acciones rápidas: clave → comando de texto que se despacha. */
    private const RUN = [
        'resumen' => '/resumen',
        'ingresos' => '/ingresos',
        'cats' => '/categorias',
        'limits' => '/limites',
        'fijos' => '/recurrentes',
        'ultimos' => '/ultimos',
        'gcat' => '/grafico categorias',
        'glim' => '/grafico limites',
        'gevo' => '/grafico evolucion',
    ];

    public function __construct(
        private readonly TelegramApi $api,
        private readonly Menu $menu,
        private readonly CommandRouter $router,
    ) {
    }

    public function handle(TelegramUser $user, string $callbackId, string $chatId, int $messageId, string $data): void
    {
        $this->api->answerCallbackQuery($callbackId);

        $parts = explode(':', $data);
        $verb = $parts[1] ?? '';
        $key = $parts[2] ?? 'main';

        if ($verb === 'nav') {
            $screen = $this->menu->screen($key);
            $this->api->editMessageText($chatId, $messageId, $screen['text'], $screen['keyboard']);

            return;
        }

        if ($verb === 'run' && isset(self::RUN[$key])) {
            $reply = $this->router->dispatch($user, $chatId, self::RUN[$key]);
            if ($reply !== '') {
                $this->api->sendMessage($chatId, $reply);
            }
        }
    }
}

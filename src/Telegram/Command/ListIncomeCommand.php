<?php

namespace App\Telegram\Command;

use App\Service\Telegram\TelegramApi;
use App\Telegram\BotContext;
use App\Telegram\Income\IncomeListPresenter;

/**
 * Últimos ingresos, con botones para borrar:  /ingresos [n]
 */
final class ListIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly IncomeListPresenter $presenter,
    ) {
    }

    public function names(): array
    {
        return ['ingresos'];
    }

    public function help(): string
    {
        return '/ingresos [n] — últimos ingresos, con botones para borrar';
    }

    public function handle(BotContext $ctx): string
    {
        $n = (int) trim($ctx->args);
        $n = $n < 1 ? 8 : min($n, 12);

        $view = $this->presenter->listView($n);
        if ($view['keyboard'] === []) {
            return $view['text']; // no hay ingresos: respuesta normal de texto
        }

        $this->api->sendMessageWithKeyboard($ctx->chatId, $view['text'], $view['keyboard']);

        return '';
    }
}

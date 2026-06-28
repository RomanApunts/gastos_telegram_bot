<?php

namespace App\Telegram\Command;

use App\Service\Telegram\TelegramApi;
use App\Telegram\BotContext;
use App\Telegram\Expense\ExpenseListPresenter;

/**
 * Últimos gastos, con botones para editar/borrar:  /ultimos [n]
 */
final class ListRecentCommand implements BotCommandInterface
{
    public function __construct(
        private readonly TelegramApi $api,
        private readonly ExpenseListPresenter $presenter,
    ) {
    }

    public function names(): array
    {
        return ['ultimos', 'últimos', 'lista'];
    }

    public function help(): string
    {
        return '/ultimos [n] — últimos gastos, con botones para editar/borrar';
    }

    public function handle(BotContext $ctx): string
    {
        $n = (int) trim($ctx->args);
        $n = $n < 1 ? 8 : min($n, 12);

        $view = $this->presenter->listView($n);
        if ($view['keyboard'] === []) {
            return $view['text']; // no hay gastos: respuesta normal de texto
        }

        $this->api->sendMessageWithKeyboard($ctx->chatId, $view['text'], $view['keyboard']);

        return '';
    }
}

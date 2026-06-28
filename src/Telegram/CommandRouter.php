<?php

namespace App\Telegram;

use App\Entity\TelegramUser;
use App\Telegram\Command\BotCommandInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Parsea el texto entrante y lo enruta al comando correspondiente.
 */
final class CommandRouter
{
    /** @var array<string, BotCommandInterface> */
    private array $byName = [];

    public function __construct(
        #[AutowireIterator('app.bot_command')] iterable $commands,
    ) {
        foreach ($commands as $command) {
            foreach ($command->names() as $name) {
                $this->byName[mb_strtolower($name)] = $command;
            }
        }
    }

    public function dispatch(TelegramUser $user, string $chatId, string $text): string
    {
        $text = trim($text);
        if ($text === '' || $text[0] !== '/') {
            return 'Escribe /menu para ver las opciones 👇';
        }

        $parts = preg_split('/\s+/', substr($text, 1), 2);
        $name = mb_strtolower($parts[0]);

        // Telegram añade "@nombre_bot" a los comandos en grupos.
        if (($at = mb_strpos($name, '@')) !== false) {
            $name = mb_substr($name, 0, $at);
        }

        $command = $this->byName[$name] ?? null;
        if ($command === null) {
            return "🤔 No conozco el comando «/{$name}». Escribe /menu para ver las opciones.";
        }

        return $command->handle(new BotContext($user, $chatId, $name, trim($parts[1] ?? '')));
    }
}

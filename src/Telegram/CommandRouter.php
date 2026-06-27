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
    /** @var BotCommandInterface[] */
    private readonly array $all;

    /** @var array<string, BotCommandInterface> */
    private array $byName = [];

    public function __construct(
        #[AutowireIterator('app.bot_command')] iterable $commands,
    ) {
        $this->all = $commands instanceof \Traversable ? iterator_to_array($commands) : $commands;
        foreach ($this->all as $command) {
            foreach ($command->names() as $name) {
                $this->byName[mb_strtolower($name)] = $command;
            }
        }
    }

    public function dispatch(TelegramUser $user, string $chatId, string $text): string
    {
        $text = trim($text);
        if ($text === '' || $text[0] !== '/') {
            return $this->help();
        }

        $parts = preg_split('/\s+/', substr($text, 1), 2);
        $name = mb_strtolower($parts[0]);

        // Telegram añade "@nombre_bot" a los comandos en grupos.
        if (($at = mb_strpos($name, '@')) !== false) {
            $name = mb_substr($name, 0, $at);
        }

        if (in_array($name, ['start', 'help', 'ayuda'], true)) {
            return $this->help();
        }

        $command = $this->byName[$name] ?? null;
        if ($command === null) {
            return "🤔 No conozco el comando «/{$name}». Escribe /ayuda para ver la lista.";
        }

        return $command->handle(new BotContext($user, $chatId, $name, trim($parts[1] ?? '')));
    }

    private function help(): string
    {
        $lines = [];
        foreach ($this->all as $command) {
            $lines[] = $command->help();
        }
        sort($lines);

        return "🤖 Comandos disponibles:\n\n" . implode("\n", $lines)
            . "\n\nLos importes admiten coma o punto (12,50). Las categorías pueden tener varias palabras.";
    }
}

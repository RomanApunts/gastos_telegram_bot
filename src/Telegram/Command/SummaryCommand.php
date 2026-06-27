<?php

namespace App\Telegram\Command;

use App\Service\SummaryReporter;
use App\Telegram\BotContext;
use App\Telegram\Util\Months;

/**
 * Resumen del mes (texto + gráficos):
 *   /resumen   ·   /resumen pasado   ·   /resumen 05/2026
 */
final class SummaryCommand implements BotCommandInterface
{
    public function __construct(
        private readonly SummaryReporter $reporter,
    ) {
    }

    public function names(): array
    {
        return ['resumen', 'informe', 'r'];
    }

    public function help(): string
    {
        return '/resumen [pasado | MM/AAAA] — situación del mes (con gráficos)';
    }

    public function handle(BotContext $ctx): string
    {
        $period = Months::resolve($ctx->args);
        if ($period === null) {
            return "❌ Formato de mes no válido. Usa /resumen, /resumen pasado o /resumen MM/AAAA.";
        }

        $this->reporter->sendTo($ctx->chatId, $period);

        return ''; // el reporter ya ha enviado el texto y los gráficos
    }
}

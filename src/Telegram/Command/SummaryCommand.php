<?php

namespace App\Telegram\Command;

use App\Service\SummaryBuilder;
use App\Telegram\BotContext;
use App\Telegram\Util\Months;

/**
 * Resumen del mes:  /resumen   ·   /resumen pasado   ·   /resumen 05/2026
 */
final class SummaryCommand implements BotCommandInterface
{
    public function __construct(
        private readonly SummaryBuilder $builder,
    ) {
    }

    public function names(): array
    {
        return ['resumen', 'informe', 'r'];
    }

    public function help(): string
    {
        return '/resumen [pasado | MM/AAAA] — situación del mes';
    }

    public function handle(BotContext $ctx): string
    {
        $period = Months::resolve($ctx->args);
        if ($period === null) {
            return "❌ Formato de mes no válido. Usa /resumen, /resumen pasado o /resumen MM/AAAA.";
        }

        return $this->builder->build($period);
    }
}

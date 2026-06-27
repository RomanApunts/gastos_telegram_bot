<?php

namespace App\Telegram\Command;

use App\Service\Chart\ChartFactory;
use App\Service\Chart\ChartRenderer;
use App\Service\Telegram\TelegramApi;
use App\Telegram\BotContext;
use App\Telegram\Util\Months;
use Psr\Log\LoggerInterface;

/**
 * Envía un gráfico como imagen:
 *   /grafico [categorias|limites|evolucion] [MM/AAAA]
 */
final class GraphCommand implements BotCommandInterface
{
    private const TYPES = [
        'categorias' => 'categorias', 'categorías' => 'categorias', 'reparto' => 'categorias',
        'limites' => 'limites', 'límites' => 'limites', 'presupuesto' => 'limites',
        'evolucion' => 'evolucion', 'evolución' => 'evolucion', 'tendencia' => 'evolucion',
    ];

    public function __construct(
        private readonly ChartFactory $charts,
        private readonly ChartRenderer $renderer,
        private readonly TelegramApi $api,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function names(): array
    {
        return ['grafico', 'gráfico', 'grafica', 'gráfica'];
    }

    public function help(): string
    {
        return '/grafico [categorias|limites|evolucion] [MM/AAAA] — gráfico visual';
    }

    public function handle(BotContext $ctx): string
    {
        // El primer token es el tipo si lo reconocemos; si no, todo es el mes.
        $parts = preg_split('/\s+/', trim($ctx->args), 2);
        $first = mb_strtolower($parts[0] ?? '');

        if ($first !== '' && isset(self::TYPES[$first])) {
            $type = self::TYPES[$first];
            $monthArg = trim($parts[1] ?? '');
        } else {
            $type = 'categorias';
            $monthArg = trim($ctx->args);
        }

        $period = Months::resolve($monthArg);
        if ($period === null) {
            return "❌ Mes no válido. Usa /grafico, /grafico limites o /grafico evolucion (opcional MM/AAAA).";
        }

        [$config, $caption] = match ($type) {
            'limites' => [$this->charts->budgetVsSpent($period), "Gastado vs. límite · {$period['label']}"],
            'evolucion' => [$this->charts->monthlyTrend(6), 'Evolución del gasto (últimos 6 meses)'],
            default => [$this->charts->categoryShare($period), "Reparto por categoría · {$period['label']}"],
        };

        if ($config === null) {
            return "📭 No hay datos suficientes para ese gráfico en {$period['label']}.";
        }

        try {
            $png = $this->renderer->render($config);
            $this->api->sendPhoto($ctx->chatId, $png, $caption);
            @unlink($png);
        } catch (\Throwable $e) {
            $this->logger->error('Generación de gráfico falló: ' . $e->getMessage());

            return '❌ No se pudo generar el gráfico. Inténtalo de nuevo más tarde.';
        }

        return ''; // la imagen ya se ha enviado
    }
}

<?php

namespace App\Telegram\Command;

use App\Service\Export\ExcelExporter;
use App\Service\Telegram\TelegramApi;
use App\Telegram\BotContext;
use App\Telegram\Util\Months;
use Psr\Log\LoggerInterface;

/**
 * Exporta los movimientos del mes (gastos + ingresos) a un Excel y lo envía:
 *   /excel   ·   /excel pasado   ·   /excel 05/2026
 */
final class GenerateExcelCommand implements BotCommandInterface
{
    public function __construct(
        private readonly ExcelExporter $exporter,
        private readonly TelegramApi $api,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function names(): array
    {
        return ['excel', 'exportar', 'export'];
    }

    public function help(): string
    {
        return '/excel [pasado | MM/AAAA] — exporta los movimientos del mes a Excel';
    }

    public function handle(BotContext $ctx): string
    {
        $period = Months::resolve($ctx->args);
        if ($period === null) {
            return "❌ Mes no válido. Usa /excel, /excel pasado o /excel MM/AAAA.";
        }

        $this->api->sendMessage($ctx->chatId, '📄 Generando el Excel…');

        try {
            $path = $this->exporter->export($period);
            $filename = 'movimientos_' . $period['start']->format('Y-m') . '.xlsx';
            $this->api->sendDocument($ctx->chatId, $path, $filename, "📄 Movimientos de {$period['label']}");
            @unlink($path);
        } catch (\Throwable $e) {
            $this->logger->error('Exportación a Excel falló: ' . $e->getMessage());

            return '❌ No se pudo generar el Excel. Inténtalo de nuevo más tarde.';
        }

        return ''; // el documento ya se ha enviado
    }
}

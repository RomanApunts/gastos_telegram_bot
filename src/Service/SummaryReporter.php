<?php

namespace App\Service;

use App\Repository\TelegramUserRepository;
use App\Service\Chart\ChartFactory;
use App\Service\Chart\ChartRenderer;
use App\Service\Telegram\TelegramApi;
use Psr\Log\LoggerInterface;

/**
 * Envía un informe de mes completo: el texto del resumen y los gráficos del
 * periodo (reparto por categoría y gastado vs. límite). Lo usan el comando
 * /resumen y el envío automático programado.
 */
final class SummaryReporter
{
    public function __construct(
        private readonly SummaryBuilder $builder,
        private readonly ChartFactory $charts,
        private readonly ChartRenderer $renderer,
        private readonly TelegramApi $api,
        private readonly TelegramUserRepository $users,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Envía el informe a un único chat.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     */
    public function sendTo(string $chatId, array $period, string $textPrefix = ''): void
    {
        $this->api->sendMessage($chatId, $textPrefix . $this->builder->build($period));

        $charts = $this->renderCharts($period);
        foreach ($charts as [$png, $caption]) {
            $this->api->sendPhoto($chatId, $png, $caption);
        }
        $this->cleanup($charts);
    }

    /**
     * Difunde el informe a todos los usuarios activos. Los gráficos se generan
     * una sola vez y se reutilizan para todos los destinatarios.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     */
    public function broadcast(array $period, string $textPrefix = ''): int
    {
        $text = $textPrefix . $this->builder->build($period);
        $charts = $this->renderCharts($period);

        $recipients = 0;
        foreach ($this->users->findAllActive() as $user) {
            $this->api->sendMessage($user->getTelegramId(), $text);
            foreach ($charts as [$png, $caption]) {
                $this->api->sendPhoto($user->getTelegramId(), $png, $caption);
            }
            ++$recipients;
        }

        $this->cleanup($charts);

        return $recipients;
    }

    /**
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     *
     * @return array<int, array{0: string, 1: string}> pares [ruta PNG, pie de foto]
     */
    private function renderCharts(array $period): array
    {
        $specs = [
            [$this->charts->categoryShare($period), "Reparto por categoría · {$period['label']}"],
            [$this->charts->budgetVsSpent($period), "Gastado vs. límite · {$period['label']}"],
        ];

        $rendered = [];
        foreach ($specs as [$config, $caption]) {
            if ($config === null) {
                continue;
            }
            try {
                $rendered[] = [$this->renderer->render($config), $caption];
            } catch (\Throwable $e) {
                $this->logger->error('Gráfico del informe falló: ' . $e->getMessage());
            }
        }

        return $rendered;
    }

    /**
     * @param array<int, array{0: string, 1: string}> $charts
     */
    private function cleanup(array $charts): void
    {
        foreach ($charts as [$png]) {
            @unlink($png);
        }
    }
}

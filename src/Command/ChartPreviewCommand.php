<?php

namespace App\Command;

use App\Service\Chart\ChartFactory;
use App\Service\Chart\ChartRenderer;
use App\Telegram\Util\Months;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Renderiza un gráfico a un PNG local, sin enviarlo a Telegram. Útil para
 * previsualizar y depurar. Ej: php bin/console app:chart:preview categorias
 */
#[AsCommand(
    name: 'app:chart:preview',
    description: 'Genera un gráfico (categorias|limites|evolucion) en un PNG local',
)]
final class ChartPreviewCommand extends Command
{
    public function __construct(
        private readonly ChartFactory $charts,
        private readonly ChartRenderer $renderer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'categorias | limites | evolucion', 'categorias')
            ->addArgument('output', InputArgument::OPTIONAL, 'Ruta del PNG de salida', 'var/chart-preview.png')
            ->addArgument('month', InputArgument::OPTIONAL, 'actual | pasado | MM/AAAA', 'actual');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = (string) $input->getArgument('type');
        $dest = (string) $input->getArgument('output');

        $period = Months::resolve((string) $input->getArgument('month'));
        if ($period === null) {
            $io->error('Mes no válido.');

            return Command::INVALID;
        }

        $config = match ($type) {
            'limites' => $this->charts->budgetVsSpent($period),
            'evolucion' => $this->charts->monthlyTrend(6),
            'categorias' => $this->charts->categoryShare($period),
            default => null,
        };

        if ($config === null) {
            $io->warning("Sin datos para «{$type}» o tipo no válido.");

            return Command::FAILURE;
        }

        $png = $this->renderer->render($config);
        @rename($png, $dest) || copy($png, $dest);
        $io->success("Gráfico generado en {$dest}");

        return Command::SUCCESS;
    }
}

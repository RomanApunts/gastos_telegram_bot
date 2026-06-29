<?php

namespace App\Command;

use App\Service\Export\ExcelExporter;
use App\Telegram\Util\Months;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera el Excel de movimientos de un mes en un archivo local, sin enviarlo.
 * Ej: php bin/console app:excel:preview actual var/export.xlsx
 */
#[AsCommand(
    name: 'app:excel:preview',
    description: 'Genera el Excel de movimientos de un mes en un archivo local',
)]
final class ExcelPreviewCommand extends Command
{
    public function __construct(
        private readonly ExcelExporter $exporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('month', InputArgument::OPTIONAL, 'actual | pasado | MM/AAAA', 'actual')
            ->addArgument('output', InputArgument::OPTIONAL, 'Ruta del .xlsx de salida', 'var/export-preview.xlsx');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $period = Months::resolve((string) $input->getArgument('month'));
        if ($period === null) {
            $io->error('Mes no válido.');

            return Command::INVALID;
        }

        $dest = (string) $input->getArgument('output');
        $path = $this->exporter->export($period);
        @rename($path, $dest) || copy($path, $dest);

        $io->success("Excel de {$period['label']} generado en {$dest}");

        return Command::SUCCESS;
    }
}

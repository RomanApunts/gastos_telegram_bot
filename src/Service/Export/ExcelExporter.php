<?php

namespace App\Service\Export;

use App\Entity\Expense;
use App\Entity\Income;
use App\Repository\ExpenseRepository;
use App\Repository\IncomeRepository;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Genera un libro Excel (.xlsx) con los gastos e ingresos de un periodo,
 * más una hoja de resumen con totales y balance.
 */
final class ExcelExporter
{
    private const MONEY_FORMAT = '#,##0.00" €"';

    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly IncomeRepository $incomes,
    ) {
    }

    /**
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     *
     * @return string ruta del .xlsx generado (el llamador debe borrarlo)
     */
    public function export(array $period): string
    {
        $expenses = $this->expenses->findForPeriod($period['start'], $period['end']);
        $incomes = $this->incomes->findForPeriod($period['start'], $period['end']);

        $book = new Spreadsheet();

        $resumen = $book->getActiveSheet();
        $resumen->setTitle('Resumen');
        $gastos = $book->createSheet();
        $gastos->setTitle('Gastos');
        $ingresos = $book->createSheet();
        $ingresos->setTitle('Ingresos');

        $totalGastos = $this->fillExpenses($gastos, $expenses);
        $totalIngresos = $this->fillIncomes($ingresos, $incomes);
        $this->fillSummary($resumen, $period['label'], $totalGastos, $totalIngresos);

        $book->setActiveSheetIndex(0);

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('export_', true) . '.xlsx';
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    /**
     * @param Expense[] $expenses
     */
    private function fillExpenses(Worksheet $sheet, array $expenses): float
    {
        $sheet->fromArray(['Fecha', 'Categoría', 'Importe', 'Descripción', 'Registrado por'], null, 'A1');
        $this->styleHeader($sheet, 'A1:E1');

        $row = 2;
        $total = 0.0;
        foreach ($expenses as $e) {
            $amount = (float) $e->getAmount();
            $sheet->setCellValue("A{$row}", ExcelDate::PHPToExcel($e->getSpentAt()));
            $sheet->setCellValue("B{$row}", $e->getCategory()->getName());
            $sheet->setCellValue("C{$row}", $amount);
            $sheet->setCellValue("D{$row}", $e->getDescription() ?? '');
            $sheet->setCellValue("E{$row}", $e->getUser()->getName());
            $total += $amount;
            ++$row;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("C{$row}", $total);
        $sheet->getStyle("B{$row}:C{$row}")->getFont()->setBold(true);

        if ($row > 2) {
            $sheet->getStyle('A2:A' . ($row - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
        $sheet->getStyle("C2:C{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $this->widths($sheet, ['A' => 12, 'B' => 18, 'C' => 14, 'D' => 32, 'E' => 16]);

        return $total;
    }

    /**
     * @param Income[] $incomes
     */
    private function fillIncomes(Worksheet $sheet, array $incomes): float
    {
        $sheet->fromArray(['Fecha', 'Importe', 'Descripción', 'Registrado por'], null, 'A1');
        $this->styleHeader($sheet, 'A1:D1');

        $row = 2;
        $total = 0.0;
        foreach ($incomes as $i) {
            $amount = (float) $i->getAmount();
            $sheet->setCellValue("A{$row}", ExcelDate::PHPToExcel($i->getReceivedAt()));
            $sheet->setCellValue("B{$row}", $amount);
            $sheet->setCellValue("C{$row}", $i->getDescription() ?? '');
            $sheet->setCellValue("D{$row}", $i->getUser()->getName());
            $total += $amount;
            ++$row;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("B{$row}", $total);
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);

        if ($row > 2) {
            $sheet->getStyle('A2:A' . ($row - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        }
        $sheet->getStyle("B2:B{$row}")->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $this->widths($sheet, ['A' => 12, 'B' => 14, 'C' => 32, 'D' => 16]);

        return $total;
    }

    private function fillSummary(Worksheet $sheet, string $label, float $gastos, float $ingresos): void
    {
        $sheet->setCellValue('A1', "Resumen de {$label}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A3', 'Total ingresos');
        $sheet->setCellValue('B3', $ingresos);
        $sheet->setCellValue('A4', 'Total gastos');
        $sheet->setCellValue('B4', $gastos);
        $sheet->setCellValue('A5', 'Balance');
        $sheet->setCellValue('B5', $ingresos - $gastos);

        $sheet->getStyle('A3:A5')->getFont()->setBold(true);
        $sheet->getStyle('B3:B5')->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $this->widths($sheet, ['A' => 18, 'B' => 14]);
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4E79A7');
    }

    /**
     * @param array<string, int> $widths
     */
    private function widths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}

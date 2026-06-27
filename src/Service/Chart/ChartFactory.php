<?php

namespace App\Service\Chart;

use App\Repository\CategoryBudgetRepository;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;

/**
 * Construye configuraciones de Chart.js a partir de los datos de gasto.
 * Cada método devuelve null si no hay datos suficientes para el gráfico.
 */
final class ChartFactory
{
    private const PALETTE = [
        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f',
        '#edc948', '#b07aa1', '#ff9da7', '#9c755f', '#bab0ac',
    ];

    private const SHORT_MONTHS = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ExpenseRepository $expenses,
        private readonly CategoryBudgetRepository $budgets,
    ) {
    }

    /**
     * Donut con el reparto de gasto por categoría en el mes.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     *
     * @return array<string, mixed>|null
     */
    public function categoryShare(array $period): ?array
    {
        $totals = $this->expenses->sumByCategoryForPeriod($period['start'], $period['end']);

        $labels = [];
        $data = [];
        $colors = [];
        $i = 0;
        foreach ($this->categories->findActiveOrdered() as $cat) {
            $amount = (float) ($totals[$cat->getId()] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $labels[] = $cat->getName();
            $data[] = $amount;
            $colors[] = self::PALETTE[$i % count(self::PALETTE)];
            ++$i;
        }

        if ($data === []) {
            return null;
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ],
            'options' => [
                'legend' => ['position' => 'bottom', 'labels' => ['fontSize' => 14, 'padding' => 14]],
                'title' => [
                    'display' => true,
                    'fontSize' => 18,
                    'text' => "Reparto por categoría · {$period['label']}",
                ],
            ],
        ];
    }

    /**
     * Barras horizontales: gastado frente al límite de cada categoría con tope.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, label: string} $period
     *
     * @return array<string, mixed>|null
     */
    public function budgetVsSpent(array $period): ?array
    {
        $spentMap = $this->expenses->sumByCategoryForPeriod($period['start'], $period['end']);
        $limitMap = $this->budgets->findEffectiveAmountsForMonth($period['start']);

        $labels = [];
        $spent = [];
        $limit = [];
        foreach ($this->categories->findActiveOrdered() as $cat) {
            if (!isset($limitMap[$cat->getId()])) {
                continue; // solo categorías con límite definido
            }
            $labels[] = $cat->getName();
            $spent[] = (float) ($spentMap[$cat->getId()] ?? 0);
            $limit[] = (float) $limitMap[$cat->getId()];
        }

        if ($labels === []) {
            return null;
        }

        return [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Gastado',
                        'data' => $spent,
                        'backgroundColor' => '#e15759',
                    ],
                    [
                        'label' => 'Límite',
                        'data' => $limit,
                        'backgroundColor' => '#bab0ac',
                    ],
                ],
            ],
            'options' => [
                'legend' => ['position' => 'top'],
                'title' => [
                    'display' => true,
                    'fontSize' => 18,
                    'text' => "Gastado vs. límite · {$period['label']}",
                ],
                'scales' => [
                    'xAxes' => [['ticks' => ['beginAtZero' => true]]],
                ],
            ],
        ];
    }

    /**
     * Barras con el gasto total de los últimos N meses.
     *
     * @return array<string, mixed>|null
     */
    public function monthlyTrend(int $months = 6): ?array
    {
        $current = (new \DateTimeImmutable('today'))->modify('first day of this month')->setTime(0, 0, 0);

        $labels = [];
        $data = [];
        $hasData = false;
        for ($i = $months - 1; $i >= 0; --$i) {
            $start = $current->modify("-{$i} months");
            $end = $start->modify('first day of next month');
            $total = (float) $this->expenses->sumForPeriod($start, $end);

            $labels[] = self::SHORT_MONTHS[(int) $start->format('n')] . ' ' . $start->format('y');
            $data[] = $total;
            $hasData = $hasData || $total > 0;
        }

        if (!$hasData) {
            return null;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Gasto total',
                    'data' => $data,
                    'backgroundColor' => '#4e79a7',
                ]],
            ],
            'options' => [
                'legend' => ['display' => false],
                'title' => [
                    'display' => true,
                    'fontSize' => 18,
                    'text' => "Evolución del gasto · últimos {$months} meses",
                ],
                'scales' => [
                    'yAxes' => [['ticks' => ['beginAtZero' => true]]],
                ],
            ],
        ];
    }
}

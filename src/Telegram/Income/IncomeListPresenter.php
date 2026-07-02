<?php

namespace App\Telegram\Income;

use App\Entity\Income;
use App\Repository\IncomeRepository;
use App\Telegram\Util\Money;

/**
 * Construye las vistas (texto + teclado inline) para gestionar ingresos:
 * lista, detalle y confirmación de borrado. Los ingresos no tienen categoría,
 * así que la única acción es borrar.
 */
final class IncomeListPresenter
{
    private const LIST_SIZE = 8;

    public function __construct(
        private readonly IncomeRepository $incomes,
    ) {
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function listView(int $limit = self::LIST_SIZE): array
    {
        $items = $this->incomes->findRecent($limit);
        if ($items === []) {
            return ['text' => 'No hay ingresos registrados todavía.', 'keyboard' => []];
        }

        $rows = array_map(
            fn (Income $i) => [['text' => $this->label($i), 'callback_data' => "i:v:{$i->getId()}"]],
            $items,
        );

        return [
            'text' => '💰 Últimos ingresos — pulsa uno para borrar:',
            'keyboard' => $rows,
        ];
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function detailView(Income $i): array
    {
        return [
            'text' => $this->detailText($i),
            'keyboard' => [
                [['text' => '🗑️ Borrar', 'callback_data' => "i:delq:{$i->getId()}"]],
                [['text' => '⬅️ Volver', 'callback_data' => 'i:list']],
            ],
        ];
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function confirmDeleteView(Income $i): array
    {
        return [
            'text' => $this->detailText($i) . "\n\n¿Seguro que quieres borrarlo?",
            'keyboard' => [[
                ['text' => '✅ Sí, borrar', 'callback_data' => "i:del:{$i->getId()}"],
                ['text' => '❌ No', 'callback_data' => "i:v:{$i->getId()}"],
            ]],
        ];
    }

    private function label(Income $i): string
    {
        $desc = $i->getDescription() !== null ? " · {$i->getDescription()}" : '';

        return "#{$i->getId()} · {$i->getReceivedAt()->format('d/m')} · "
            . Money::format($i->getAmount()) . $desc;
    }

    private function detailText(Income $i): string
    {
        $note = $i->getDescription() !== null ? "\n📝 {$i->getDescription()}" : '';

        return "💰 Ingreso #{$i->getId()}\n"
            . '💶 ' . Money::format($i->getAmount()) . "\n"
            . '📅 ' . $i->getReceivedAt()->format('d/m/Y')
            . $note . "\n"
            . "— {$i->getUser()->getName()}";
    }
}

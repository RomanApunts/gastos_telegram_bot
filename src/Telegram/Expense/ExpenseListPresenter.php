<?php

namespace App\Telegram\Expense;

use App\Entity\Category;
use App\Entity\Expense;
use App\Repository\CategoryRepository;
use App\Repository\ExpenseRepository;
use App\Telegram\Util\Money;

/**
 * Construye las vistas (texto + teclado inline) para gestionar gastos:
 * lista, detalle, confirmación de borrado y selección de categoría.
 */
final class ExpenseListPresenter
{
    private const LIST_SIZE = 8;

    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly CategoryRepository $categories,
    ) {
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function listView(int $limit = self::LIST_SIZE): array
    {
        $items = $this->expenses->findRecent($limit);
        if ($items === []) {
            return ['text' => 'No hay gastos registrados todavía.', 'keyboard' => []];
        }

        $rows = array_map(
            fn (Expense $e) => [['text' => $this->label($e), 'callback_data' => "e:v:{$e->getId()}"]],
            $items,
        );

        return [
            'text' => '🧾 Últimos gastos — pulsa uno para editar o borrar:',
            'keyboard' => $rows,
        ];
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function detailView(Expense $e): array
    {
        return [
            'text' => $this->detailText($e),
            'keyboard' => [
                [
                    ['text' => '🗑️ Borrar', 'callback_data' => "e:delq:{$e->getId()}"],
                    ['text' => '🏷️ Categoría', 'callback_data' => "e:cat:{$e->getId()}"],
                ],
                [['text' => '⬅️ Volver', 'callback_data' => 'e:list']],
            ],
        ];
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function confirmDeleteView(Expense $e): array
    {
        return [
            'text' => $this->detailText($e) . "\n\n¿Seguro que quieres borrarlo?",
            'keyboard' => [[
                ['text' => '✅ Sí, borrar', 'callback_data' => "e:del:{$e->getId()}"],
                ['text' => '❌ No', 'callback_data' => "e:v:{$e->getId()}"],
            ]],
        ];
    }

    /**
     * @return array{text: string, keyboard: array<int, array<int, array{text: string, callback_data: string}>>}
     */
    public function categoryView(Expense $e): array
    {
        $rows = [];
        $row = [];
        foreach ($this->categories->findActiveOrdered() as $category) {
            $row[] = ['text' => $category->getName(), 'callback_data' => "e:setcat:{$e->getId()}:{$category->getId()}"];
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row !== []) {
            $rows[] = $row;
        }
        $rows[] = [['text' => '⬅️ Volver', 'callback_data' => "e:v:{$e->getId()}"]];

        return [
            'text' => $this->detailText($e) . "\n\nElige nueva categoría:",
            'keyboard' => $rows,
        ];
    }

    private function label(Expense $e): string
    {
        return "#{$e->getId()} · {$e->getSpentAt()->format('d/m')} · "
            . "{$e->getCategory()->getName()} · " . Money::format($e->getAmount());
    }

    private function detailText(Expense $e): string
    {
        $note = $e->getDescription() !== null ? "\n🏪 {$e->getDescription()}" : '';

        return "🧾 Gasto #{$e->getId()}\n"
            . '💶 ' . Money::format($e->getAmount()) . "\n"
            . '📅 ' . $e->getSpentAt()->format('d/m/Y') . "\n"
            . "🏷️ {$e->getCategory()->getName()}"
            . $note . "\n"
            . "— {$e->getUser()->getName()}";
    }
}

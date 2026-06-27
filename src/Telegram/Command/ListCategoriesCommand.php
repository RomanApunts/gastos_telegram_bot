<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Telegram\BotContext;

/**
 * Lista las categorías:  /categorias
 */
final class ListCategoriesCommand implements BotCommandInterface
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {
    }

    public function names(): array
    {
        return ['categorias', 'categorías', 'cats'];
    }

    public function help(): string
    {
        return '/categorias — lista las categorías';
    }

    public function handle(BotContext $ctx): string
    {
        $cats = $this->categories->findActiveOrdered();
        if ($cats === []) {
            return 'No hay categorías todavía. Crea una con /nuevacategoria <nombre>.';
        }

        $lines = array_map(fn (Category $c) => '• ' . $c->getName(), $cats);

        return "🏷️ Categorías:\n" . implode("\n", $lines);
    }
}

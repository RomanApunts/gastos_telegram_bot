<?php

namespace App\Telegram;

use App\Entity\Category;
use App\Repository\CategoryRepository;

/**
 * Reconoce el nombre de una categoría (posiblemente con varias palabras) al
 * inicio de un texto, devolviendo la categoría y el texto sobrante (descripción).
 */
final class CategoryMatcher
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {
    }

    /**
     * @return array{0: Category|null, 1: string|null} [categoría, descripción|null]
     */
    public function match(string $text): array
    {
        $cats = $this->categories->findActiveOrdered();
        // Más largos primero para que "Comida fuera" gane a "Comida".
        usort($cats, fn (Category $a, Category $b) => mb_strlen($b->getName()) <=> mb_strlen($a->getName()));

        $lower = mb_strtolower($text);
        foreach ($cats as $cat) {
            $name = mb_strtolower($cat->getName());
            $len = mb_strlen($name);
            if (mb_substr($lower, 0, $len) === $name
                && ($len === mb_strlen($lower) || mb_substr($lower, $len, 1) === ' ')) {
                $rest = trim(mb_substr($text, $len));

                return [$cat, $rest === '' ? null : $rest];
            }
        }

        return [null, null];
    }
}

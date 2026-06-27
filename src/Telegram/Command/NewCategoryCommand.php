<?php

namespace App\Telegram\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Telegram\BotContext;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crea una categoría:  /nuevacategoria Comida
 */
final class NewCategoryCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function names(): array
    {
        return ['nuevacategoria', 'nuevacat'];
    }

    public function help(): string
    {
        return '/nuevacategoria <nombre> — crea una categoría';
    }

    public function handle(BotContext $ctx): string
    {
        $name = trim($ctx->args);
        if ($name === '') {
            return 'Uso: /nuevacategoria <nombre>';
        }
        if (mb_strlen($name) > 60) {
            return '❌ El nombre es demasiado largo (máx. 60 caracteres).';
        }

        foreach ($this->categories->findBy([]) as $existing) {
            if (mb_strtolower($existing->getName()) === mb_strtolower($name)) {
                return "ℹ️ La categoría «{$existing->getName()}» ya existe.";
            }
        }

        $category = new Category($name);
        $this->em->persist($category);
        $this->em->flush();

        return "✅ Categoría «{$name}» creada.";
    }
}

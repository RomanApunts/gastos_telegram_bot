<?php

namespace App\Telegram\Command;

use App\Entity\Income;
use App\Telegram\BotContext;
use App\Telegram\Util\Money;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Registra un ingreso:  /ingreso 1800 nómina
 */
final class AddIncomeCommand implements BotCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function names(): array
    {
        return ['ingreso', 'ing'];
    }

    public function help(): string
    {
        return '/ingreso <importe> [descripción] — registra un ingreso';
    }

    public function handle(BotContext $ctx): string
    {
        if ($ctx->args === '') {
            return "Uso: /ingreso <importe> [descripción]\nEj: /ingreso 1800 nómina";
        }

        $parts = preg_split('/\s+/', $ctx->args, 2);
        $amount = Money::parse($parts[0]);
        if ($amount === null) {
            return "❌ Importe no válido: «{$parts[0]}». Ejemplo: 1800";
        }

        $description = trim($parts[1] ?? '');
        $income = new Income(
            $ctx->user,
            $amount,
            new \DateTimeImmutable('today'),
            $description === '' ? null : $description,
        );
        $this->em->persist($income);
        $this->em->flush();

        $desc = $description !== '' ? " ({$description})" : '';

        return '✅ Ingreso registrado: ' . Money::format($amount) . $desc;
    }
}

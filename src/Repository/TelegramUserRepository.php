<?php

namespace App\Repository;

use App\Entity\TelegramUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramUser>
 */
class TelegramUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramUser::class);
    }

    public function findActiveByTelegramId(string $telegramId): ?TelegramUser
    {
        return $this->findOneBy(['telegramId' => $telegramId, 'active' => true]);
    }

    /** @return TelegramUser[] */
    public function findAllActive(): array
    {
        return $this->findBy(['active' => true]);
    }
}

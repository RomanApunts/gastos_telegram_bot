<?php

namespace App\Entity;

use App\Repository\TelegramUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Usuario autorizado a usar el bot (whitelist).
 * Solo los telegram_id registrados aquí pueden interactuar.
 */
#[ORM\Entity(repositoryClass: TelegramUserRepository::class)]
class TelegramUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** ID de Telegram del chat/usuario. Es bigint, por eso string. */
    #[ORM\Column(type: Types::BIGINT, unique: true)]
    private string $telegramId;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $telegramId, string $name)
    {
        $this->telegramId = $telegramId;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTelegramId(): string
    {
        return $this->telegramId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

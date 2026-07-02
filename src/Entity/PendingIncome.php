<?php

namespace App\Entity;

use App\Repository\PendingIncomeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ingreso interpretado de una nota de voz que espera la confirmación del
 * usuario (botones inline). Se elimina al confirmar o cancelar.
 */
#[ORM\Entity(repositoryClass: PendingIncomeRepository::class)]
class PendingIncome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TelegramUser::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TelegramUser $user;

    /** Chat donde responder/editar el mensaje de confirmación. */
    #[ORM\Column(type: Types::BIGINT)]
    private string $chatId;

    /** ID del mensaje del bot con los botones (para editarlo al resolver). */
    #[ORM\Column(nullable: true)]
    private ?int $messageId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        TelegramUser $user,
        string $chatId,
        string $amount,
        \DateTimeImmutable $receivedAt,
        ?string $description = null,
    ) {
        $this->user = $user;
        $this->chatId = $chatId;
        $this->amount = $amount;
        $this->receivedAt = $receivedAt;
        $this->description = $description;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): TelegramUser
    {
        return $this->user;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    public function setMessageId(?int $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at;

    #[ORM\ManyToOne(inversedBy: 'chatMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ChatDiscussion $chatDiscussion;

    #[ORM\Column]
    private ?bool $isRead = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct(ChatDiscussion $chatDiscussion, User $owner, string $message, ?string $image = null)
    {
        $this->chatDiscussion = $chatDiscussion;
        $this->owner = $owner;
        $this->message = $message;
        $this->image = $image;
        $this->created_at = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $this->isRead = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getChatDiscussion(): ?ChatDiscussion
    {
        return $this->chatDiscussion;
    }

    public function setChatDiscussion(?ChatDiscussion $chatDiscussion): self
    {
        $this->chatDiscussion = $chatDiscussion;

        return $this;
    }

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\ChatDiscussionRepository;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatDiscussionRepository::class)]
class ChatDiscussion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chatDiscussions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recipient;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastMessageAt;

    #[ORM\OneToMany(mappedBy: 'chatDiscussion', targetEntity: ChatMessage::class, orphanRemoval: true)]
    private Collection $chatMessages;

    #[ORM\Column]
    private ?bool $openUser;

    #[ORM\Column]
    private ?bool $openRecipient;

    #[ORM\Column]
    private ?bool $typingUser;

    #[ORM\Column]
    private ?bool $typingRecipient;

    public function __construct($user, $recipient)
    {
        $this->user = $user;
        $this->recipient = $recipient;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastMessageAt = new DateTime();
        $this->chatMessages = new ArrayCollection();
        $this->openUser = true;
        $this->openRecipient = false;
        $this->typingUser = false;
        $this->typingRecipient = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeInterface
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(\DateTimeInterface $lastMessageAt): self
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ChatMessage $chatMessage): self
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages->add($chatMessage);
            $chatMessage->setChatDiscussion($this);
        }

        return $this;
    }

    public function removeChatMessage(ChatMessage $chatMessage): self
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getChatDiscussion() === $this) {
                $chatMessage->setChatDiscussion(null);
            }
        }

        return $this;
    }

    public function isOpenUser(): ?bool
    {
        return $this->openUser;
    }

    public function setOpenUser(bool $openUser): self
    {
        $this->openUser = $openUser;

        return $this;
    }

    public function isOpenRecipient(): ?bool
    {
        return $this->openRecipient;
    }

    public function setOpenRecipient(?bool $openRecipient): void
    {
        $this->openRecipient = $openRecipient;
    }

    public function isTypingUser(): ?bool
    {
        return $this->typingUser;
    }

    public function setTypingUser(?bool $typingUser): void
    {
        $this->typingUser = $typingUser;
    }

    public function isTypingRecipient(): ?bool
    {
        return $this->typingRecipient;
    }

    public function setTypingRecipient(?bool $typingRecipient): void
    {
        $this->typingRecipient = $typingRecipient;
    }
}

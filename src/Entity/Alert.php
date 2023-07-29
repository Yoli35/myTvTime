<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AlertRepository;
use App\Service\DateService;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
#[ApiResource]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(length: 255)]
    private ?string $message;

    #[ORM\Column]
    private ?bool $activated;

    #[ORM\Column]
    private ?DateTimeImmutable $date;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?int $serieViewingId = null;

    #[ORM\Column(nullable: true)]
    private ?int $providerId = null;

    public function __construct($user, $serieViewingId, $date, $message, DateService $dateService)
    {
        $this->user = $user;
        $this->serieViewingId = $serieViewingId;
        $this->message = $message;
        $this->activated = true;
        $this->date = $date;
        $this->createdAt = $dateService->newDateImmutable('now', 'Europe/Paris');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): static
    {
        $this->activated = $activated;

        return $this;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSerieViewingId(): ?int
    {
        return $this->serieViewingId;
    }

    public function setSerieViewingId(?int $serieViewingId): static
    {
        $this->serieViewingId = $serieViewingId;

        return $this;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function setProviderId(?int $providerId): static
    {
        $this->providerId = $providerId;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\FavoriteRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $mediaId = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    public function __construct(int $userID, int $mediaId, string $type)
    {
        $this->userId = $userID;
        $this->mediaId = $mediaId;
        $this->type = $type;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMediaId(): ?int
    {
        return $this->mediaId;
    }

    public function setMediaId(int $mediaId): self
    {
        $this->mediaId = $mediaId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}

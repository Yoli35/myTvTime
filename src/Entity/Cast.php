<?php

namespace App\Entity;

use App\Repository\CastRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CastRepository::class)]
class Cast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $castId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePath = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCastId(): ?int
    {
        return $this->castId;
    }

    public function setCastId(int $castId): self
    {
        $this->castId = $castId;

        return $this;
    }

    public function getProfilePath(): ?string
    {
        return $this->profilePath;
    }

    public function setProfilePath(?string $profilePath): self
    {
        $this->profilePath = $profilePath;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}

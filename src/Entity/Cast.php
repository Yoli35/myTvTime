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
    private ?int $tmdbId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePath = null;

    public function __construct($tmdbId, $name, $profilePath)
    {
        $this->tmdbId = $tmdbId;
        $this->name = $name;
        $this->profilePath = $profilePath;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): self
    {
        $this->tmdbId = $tmdbId;

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

<?php

namespace App\Entity;

use App\Repository\SerieBackdropRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieBackdropRepository::class)]
class SerieBackdrop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serieBackdrops')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $serie = null;

    #[ORM\Column(length: 255)]
    private ?string $backdropPath = null;

    public function __construct(Serie $serie, string $backdropPath)
    {
        $this->serie = $serie;
        $this->backdropPath = $backdropPath;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;

        return $this;
    }

    public function getBackdropPath(): ?string
    {
        return $this->backdropPath;
    }

    public function setBackdropPath(string $backdropPath): static
    {
        $this->backdropPath = $backdropPath;

        return $this;
    }
}

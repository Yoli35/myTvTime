<?php

namespace App\Entity;

use App\Repository\SeriePosterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeriePosterRepository::class)]
class SeriePoster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $posterPath;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'seriePosters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $serie;

    public function __construct(Serie $serie, string $posterPath)
    {
        $this->serie = $serie;
        $this->posterPath = $posterPath;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(string $posterPath): static
    {
        $this->posterPath = $posterPath;

        return $this;
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
}

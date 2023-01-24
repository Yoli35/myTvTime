<?php

namespace App\Entity;

use App\Repository\SerieCastRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieCastRepository::class)]
class SerieCast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serieCasts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SerieViewing $serieViewing = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cast $cast = null;

    #[ORM\Column]
    private ?int $numberOfEpisodes = null;

    #[ORM\Column]
    private ?bool $recurringCharacter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerieViewing(): ?SerieViewing
    {
        return $this->serieViewing;
    }

    public function setSerieViewing(?SerieViewing $serieViewing): self
    {
        $this->serieViewing = $serieViewing;

        return $this;
    }

    public function getCast(): ?Cast
    {
        return $this->cast;
    }

    public function setCast(Cast $cast): self
    {
        $this->cast = $cast;

        return $this;
    }

    public function getNumberOfEpisodes(): ?int
    {
        return $this->numberOfEpisodes;
    }

    public function setNumberOfEpisodes(int $numberOfEpisodes): self
    {
        $this->numberOfEpisodes = $numberOfEpisodes;

        return $this;
    }

    public function isRecurringCharacter(): ?bool
    {
        return $this->recurringCharacter;
    }

    public function setRecurringCharacter(bool $recurringCharacter): self
    {
        $this->recurringCharacter = $recurringCharacter;

        return $this;
    }
}

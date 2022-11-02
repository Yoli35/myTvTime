<?php

namespace App\Entity;

use App\Repository\EpisodeViewingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EpisodeViewingRepository::class)]
class EpisodeViewing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $firstAirAt = null;

    #[ORM\Column]
    private ?int $episodeNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SeasonViewing $season = null;

    public function __construct($episodeNumber)
    {
        $this->episodeNumber = $episodeNumber;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstAirAt(): ?DateTimeImmutable
    {
        return $this->firstAirAt;
    }

    public function setFirstAirAt(?DateTimeImmutable $firstAirAt): self
    {
        $this->firstAirAt = $firstAirAt;

        return $this;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): self
    {
        $this->episodeNumber = $episodeNumber;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSeason(): ?SeasonViewing
    {
        return $this->season;
    }

    public function setSeason(?SeasonViewing $season): self
    {
        $this->season = $season;

        return $this;
    }
}

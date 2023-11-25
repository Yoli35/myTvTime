<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EpisodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
#[ApiResource]
class Episode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $airDate = null;

    #[ORM\Column]
    private ?int $episodeNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column]
    private ?int $tmdbId = null;

    #[ORM\Column(nullable: true)]
    private ?int $runtime = null;

    #[ORM\Column]
    private ?int $seasonNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stillPath = null;

    #[ORM\ManyToOne(inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $series = null;

    #[ORM\ManyToOne(inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Season $season = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $directLink = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAirDate(): ?\DateTimeInterface
    {
        return $this->airDate;
    }

    public function setAirDate(?\DateTimeInterface $airDate): static
    {
        $this->airDate = $airDate;

        return $this;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): static
    {
        $this->episodeNumber = $episodeNumber;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): static
    {
        $this->overview = $overview;

        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): static
    {
        $this->runtime = $runtime;

        return $this;
    }

    public function getSeasonNumber(): ?int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): static
    {
        $this->seasonNumber = $seasonNumber;

        return $this;
    }

    public function getStillPath(): ?string
    {
        return $this->stillPath;
    }

    public function setStillPath(?string $stillPath): static
    {
        $this->stillPath = $stillPath;

        return $this;
    }

    public function getSeries(): ?Serie
    {
        return $this->series;
    }

    public function setSeries(?Serie $series): static
    {
        $this->series = $series;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getDirectLink(): ?string
    {
        return $this->directLink;
    }

    public function setDirectLink(?string $directLink): static
    {
        $this->directLink = $directLink;

        return $this;
    }
}

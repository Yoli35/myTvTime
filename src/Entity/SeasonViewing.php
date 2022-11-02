<?php

namespace App\Entity;

use App\Repository\SeasonViewingRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeasonViewingRepository::class)]
class SeasonViewing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $airAt = null;

    #[ORM\Column]
    private ?int $seasonNumber = null;

    #[ORM\Column]
    private ?int $episodeCount = null;

    #[ORM\Column]
    private ?bool $seasonCompleted = null;

    #[ORM\ManyToOne(inversedBy: 'seasons')]
    private ?SerieViewing $serie = null;

    #[ORM\OneToMany(mappedBy: 'seasonViewing', targetEntity: EpisodeViewing::class)]
    private Collection $episodes;

    public function __construct($airAt, $seasonNumber, $episodeCount, $seasonCompleted)
    {
        $this->airAt = new DateTimeImmutable($airAt);
        $this->seasonNumber = $seasonNumber;
        $this->episodeCount = $episodeCount;
        $this->seasonCompleted = $seasonCompleted;
        $this->episodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAirAt(): ?DateTimeInterface
    {
        return $this->airAt;
    }

    public function setAirAt(DateTimeInterface $airAt): self
    {
        $this->airAt = $airAt;

        return $this;
    }

    public function getSeasonNumber(): ?int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): self
    {
        $this->seasonNumber = $seasonNumber;

        return $this;
    }

    public function getEpisodeCount(): ?int
    {
        return $this->episodeCount;
    }

    public function setEpisodeCount(int $episodeCount): self
    {
        $this->episodeCount = $episodeCount;

        return $this;
    }

    public function isSeasonCompleted(): ?bool
    {
        return $this->seasonCompleted;
    }

    public function setSeasonCompleted(bool $seasonCompleted): self
    {
        $this->seasonCompleted = $seasonCompleted;

        return $this;
    }

    public function getSerie(): ?SerieViewing
    {
        return $this->serie;
    }

    public function setSerie(?SerieViewing $serie): self
    {
        $this->serie = $serie;

        return $this;
    }

    /**
     * @return Collection<int, EpisodeViewing>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(EpisodeViewing $episode): self
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->setSeason($this);
        }

        return $this;
    }

    public function removeEpisodeViewing(EpisodeViewing $episode): self
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getSeason() === $this) {
                $episode->setSeason(null);
            }
        }

        return $this;
    }
}

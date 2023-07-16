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
    private ?DateTimeImmutable $airAt;

    #[ORM\Column]
    private ?int $seasonNumber;

    #[ORM\Column]
    private ?int $episodeCount;

    #[ORM\Column]
    private ?bool $seasonCompleted;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'seasons')]
    private ?SerieViewing $serieViewing = null;

    #[ORM\OneToMany(mappedBy: 'season', targetEntity: EpisodeViewing::class, cascade: ['persist', 'remove'])]
    private Collection $episodes;

    public function __construct($airAt, $seasonNumber, $episodeCount, $seasonCompleted)
    {
        $this->airAt = $airAt;
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

    public function setAirAt(DateTimeImmutable $airAt): self
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

    public function getViewedEpisodeCount(): int
    {
        $count = 0;
        /** @var EpisodeViewing $episode */
        foreach ($this->episodes as $episode) {
            if ($episode->getViewedAt()) $count++;
        }
        return $count;
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

    public function getSerieViewing(): ?SerieViewing
    {
        return $this->serieViewing;
    }

    public function setSerieViewing(?SerieViewing $serieViewing): self
    {
        $this->serieViewing = $serieViewing;

        return $this;
    }

    /**
     * @return Collection<int, EpisodeViewing>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function getEpisodeByNumber(int $number): ?EpisodeViewing
    {
        /** @var EpisodeViewing $episode */
        foreach ($this->episodes as $episode) {
            if ($episode->getEpisodeNumber() == $number) {
                return $episode;
            }
        }
        return null;
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

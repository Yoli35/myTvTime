<?php

namespace App\Entity;

use App\Repository\SerieViewingRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieViewingRepository::class)]
class SerieViewing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'serieViewings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $serie = null;

    #[ORM\Column(nullable: true)]
    private ?int $viewedEpisodes = null;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfEpisodes = null;

    #[ORM\Column]
    private ?int $numberOfSeasons = null;

    #[ORM\Column(nullable: true)]
    private ?bool $serieCompleted = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $timeShifted = 0;

    #[ORM\OneToMany(mappedBy: 'serieViewing', targetEntity: SeasonViewing::class, cascade: ['persist', 'remove'])]
    private Collection $seasons;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTime $modifiedAt;

    #[ORM\Column(nullable: true)]
    private ?int $alertId;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?EpisodeViewing $nextEpisodeToAir = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?EpisodeViewing $nextEpisodeToWatch = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $nextEpisodeCheckDate = null;

    public function __construct($alertId = null)
    {
        $this->alertId = $alertId;
        $this->createdAt = new DateTimeImmutable();
        $this->modifiedAt = new DateTime();
        $this->seasons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(Serie $serie): self
    {
        $this->serie = $serie;

        return $this;
    }

    public function getViewedEpisodes(): ?int
    {
        return $this->viewedEpisodes;
    }

    public function setViewedEpisodes(?int $viewedEpisodes): self
    {
        $this->viewedEpisodes = $viewedEpisodes;

        return $this;
    }

    /**
     * @return Collection<int, SeasonViewing>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function getSeasonByNumber(int $number): ?SeasonViewing
    {
        /** @var SeasonViewing $season */
        foreach ($this->seasons as $season) {
            if ($season->getSeasonNumber()==$number) {
                return $season;
            }
        }
        return null;
    }

    // TODO: get average network id from seasons
    public function getAverageNetworkId(): ?int
    {
        $networkIds = [];
        /** @var SeasonViewing $season */
        foreach ($this->seasons as $season) {
            $networkIds[] = $season->getNetworkId();
        }
        $networkIds = array_unique($networkIds);
        if (count($networkIds)==1) {
            return $networkIds[0];
        }
        return null;
    }

    public function addSeason(SeasonViewing $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSerieViewing($this);
        }

        return $this;
    }

    public function removeSeason(SeasonViewing $season): self
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSerieViewing() === $this) {
                $season->setSerieViewing(null);
            }
        }

        return $this;
    }

    public function isSerieCompleted(): ?bool
    {
        return $this->serieCompleted;
    }

    public function setSerieCompleted(?bool $serieCompleted): self
    {
        $this->serieCompleted = $serieCompleted;

        return $this;
    }

    public function getModifiedAt(): ?DateTime
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTime $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getNumberOfEpisodes(): ?int
    {
        return $this->numberOfEpisodes;
    }

    public function setNumberOfEpisodes(?int $numberOfEpisodes): self
    {
        $this->numberOfEpisodes = $numberOfEpisodes;

        return $this;
    }

    public function getNumberOfSeasons(): ?int
    {
        return $this->numberOfSeasons;
    }

    public function setNumberOfSeasons(?int $numberOfSeasons): void
    {
        $this->numberOfSeasons = $numberOfSeasons;
    }

    public function getTimeShifted(): int
    {
        return $this->timeShifted;
    }

    public function setTimeShifted(int $timeShifted): self
    {
        $this->timeShifted = $timeShifted;

        return $this;
    }

    public function getAlertId(): ?int
    {
        return $this->alertId;
    }

    public function setAlertId(?int $alertId): static
    {
        $this->alertId = $alertId;

        return $this;
    }

    public function getNextEpisodeToAir(): ?EpisodeViewing
    {
        return $this->nextEpisodeToAir;
    }

    public function setNextEpisodeToAir(?EpisodeViewing $nextEpisodeToAir): static
    {
        $this->nextEpisodeToAir = $nextEpisodeToAir;

        return $this;
    }

    public function getNextEpisodeToWatch(): ?EpisodeViewing
    {
        return $this->nextEpisodeToWatch;
    }

    public function setNextEpisodeToWatch(?EpisodeViewing $nextEpisodeToWatch): static
    {
        $this->nextEpisodeToWatch = $nextEpisodeToWatch;

        return $this;
    }

    public function getNextEpisodeCheckDate(): ?DateTime
    {
        return $this->nextEpisodeCheckDate;
    }

    public function setNextEpisodeCheckDate(?DateTime $nextEpisodeCheckDate): static
    {
        $this->nextEpisodeCheckDate = $nextEpisodeCheckDate;

        return $this;
    }
}

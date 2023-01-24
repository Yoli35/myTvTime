<?php

namespace App\Entity;

use App\Repository\SerieViewingRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column]
    private ?bool $specialEpisodes = false;

    #[ORM\Column]
    private ?int $seasonCount = null;

    #[ORM\OneToMany(mappedBy: 'serieViewing', targetEntity: SeasonViewing::class, cascade: ['persist'])]
    private Collection $seasons;

    #[ORM\Column(nullable: true)]
    private ?DateTime $modifiedAt;

    #[ORM\Column(nullable: true)]
    private ?bool $serieCompleted = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'serieViewing', targetEntity: SerieCast::class)]
    private Collection $serieCasts;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->modifiedAt = new DateTime();
        $this->seasons = new ArrayCollection();
        $this->serieCasts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
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

    public function isSpecialEpisodes(): ?bool
    {
        return $this->specialEpisodes;
    }

    public function setSpecialEpisodes(bool $specialEpisodes): self
    {
        $this->specialEpisodes = $specialEpisodes;

        return $this;
    }

    public function getSeasonCount(): ?int
    {
        return $this->seasonCount;
    }

    public function setSeasonCount(int $seasonCount): self
    {
        $this->seasonCount = $seasonCount;

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

    /**
     * @return Collection<int, SerieCast>
     */
    public function getSerieCasts(): Collection
    {
        return $this->serieCasts;
    }

    public function addSerieCast(SerieCast $serieCast): self
    {
        if (!$this->serieCasts->contains($serieCast)) {
            $this->serieCasts->add($serieCast);
            $serieCast->setSerieViewing($this);
        }

        return $this;
    }

    public function removeSerieCast(SerieCast $serieCast): self
    {
        if ($this->serieCasts->removeElement($serieCast)) {
            // set the owning side to null (unless already changed)
            if ($serieCast->getSerieViewing() === $this) {
                $serieCast->setSerieViewing(null);
            }
        }

        return $this;
    }
}

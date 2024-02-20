<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SerieRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ApiResource]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backdropPath = null;

    #[ORM\Column]
    private ?int $serieId = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $directLink = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $firstDateAir = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTime $updatedAt;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'series')]
    private Collection $users;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\ManyToMany(targetEntity: Networks::class)]
    private Collection $networks;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfEpisodes = 0;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfSeasons = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::JSON)]
    private array $episodeDurations = [];

    #[ORM\Column(nullable: true)]
    private ?int $upcomingDateYear = null;

    #[ORM\Column(nullable: true)]
    private ?int $upcomingDateMonth = null;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SeriePoster::class, orphanRemoval: true)]
    private Collection $seriePosters;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SerieBackdrop::class, orphanRemoval: true)]
    private Collection $serieBackdrops;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SerieCast::class)]
    private Collection $serieCasts;

    #[ORM\OneToOne(mappedBy: 'serie', cascade: ['persist', 'remove'])]
    private ?SerieLocalizedName $serieLocalizedName = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $originCountry = [];

    #[ORM\OneToMany(mappedBy: 'series', targetEntity: Episode::class, orphanRemoval: true)]
    private Collection $episodes;

    #[ORM\OneToMany(mappedBy: 'series', targetEntity: Season::class, orphanRemoval: true)]
    private Collection $seasons;

    #[ORM\OneToMany(mappedBy: 'series', targetEntity: SerieAlternateOverview::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $seriesAlternateOverviews;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTime();
        $this->networks = new ArrayCollection();
        $this->seriePosters = new ArrayCollection();
        $this->serieBackdrops = new ArrayCollection();
        $this->serieCasts = new ArrayCollection();
        $this->episodes = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->seriesAlternateOverviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstDateAir(): ?DateTimeImmutable
    {
        return $this->firstDateAir;
    }

    public function setFirstDateAir(?DateTimeImmutable $firstDateAir): self
    {
        $this->firstDateAir = $firstDateAir;

        return $this;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): self
    {
        $this->posterPath = $posterPath;

        return $this;
    }

    public function getSerieId(): ?int
    {
        return $this->serieId;
    }

    public function setSerieId(int $serieId): self
    {
        $this->serieId = $serieId;

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

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;

        return $this;
    }

    public function getBackdropPath(): ?string
    {
        return $this->backdropPath;
    }

    public function setBackdropPath(?string $backdropPath): self
    {
        $this->backdropPath = $backdropPath;

        return $this;
    }

    /**
     * @return Collection<int, Networks>
     */
    public function getNetworks(): Collection
    {
        return $this->networks;
    }

    public function addNetwork(Networks $network): self
    {
        if (!$this->networks->contains($network)) {
            $this->networks->add($network);
        }

        return $this;
    }

    public function removeNetwork(Networks $network): self
    {
        $this->networks->removeElement($network);

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

    public function setNumberOfSeasons(?int $numberOfSeasons): self
    {
        $this->numberOfSeasons = $numberOfSeasons;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getEpisodeDurations(): array
    {
        return $this->episodeDurations;
    }

    public function setEpisodeDurations(?array $episodeDurations): self
    {
        $this->episodeDurations = $episodeDurations;

        return $this;
    }

    public function getUpcomingDateYear(): ?int
    {
        return $this->upcomingDateYear;
    }

    public function setUpcomingDateYear(?int $upcomingDateYear): static
    {
        $this->upcomingDateYear = $upcomingDateYear;

        return $this;
    }

    public function getUpcomingDateMonth(): ?int
    {
        return $this->upcomingDateMonth;
    }

    public function setUpcomingDateMonth(?int $upcomingDateMonth): static
    {
        $this->upcomingDateMonth = $upcomingDateMonth;

        return $this;
    }

    /**
     * @return Collection<int, SeriePoster>
     */
    public function getSeriePosters(): Collection
    {
        return $this->seriePosters;
    }

    public function addSeriePoster(SeriePoster $seriePoster): static
    {
        if (!$this->seriePosters->contains($seriePoster)) {
            $this->seriePosters->add($seriePoster);
            $seriePoster->setSerie($this);
        }

        return $this;
    }

    public function removeSeriePoster(SeriePoster $seriePoster): static
    {
        if ($this->seriePosters->removeElement($seriePoster)) {
            // set the owning side to null (unless already changed)
            if ($seriePoster->getSerie() === $this) {
                $seriePoster->setSerie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SerieBackdrop>
     */
    public function getSerieBackdrops(): Collection
    {
        return $this->serieBackdrops;
    }

    public function addSerieBackdrop(SerieBackdrop $serieBackdrop): static
    {
        if (!$this->serieBackdrops->contains($serieBackdrop)) {
            $this->serieBackdrops->add($serieBackdrop);
            $serieBackdrop->setSerie($this);
        }

        return $this;
    }

    public function removeSerieBackdrop(SerieBackdrop $serieBackdrop): static
    {
        if ($this->serieBackdrops->removeElement($serieBackdrop)) {
            // set the owning side to null (unless already changed)
            if ($serieBackdrop->getSerie() === $this) {
                $serieBackdrop->setSerie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SerieCast>
     */
    public function getSerieCasts(): Collection
    {
        return $this->serieCasts;
    }

    public function addSerieCast(SerieCast $serieCast): static
    {
        if (!$this->serieCasts->contains($serieCast)) {
            $this->serieCasts->add($serieCast);
            $serieCast->setSerie($this);
        }

        return $this;
    }

    public function removeSerieCast(SerieCast $serieCast): static
    {
        if ($this->serieCasts->removeElement($serieCast)) {
            // set the owning side to null (unless already changed)
            if ($serieCast->getSerie() === $this) {
                $serieCast->setSerie(null);
            }
        }

        return $this;
    }

    public function getSerieLocalizedName(): ?SerieLocalizedName
    {
        return $this->serieLocalizedName;
    }

    public function setSerieLocalizedName(SerieLocalizedName $serieLocalizedName): static
    {
        // set the owning side of the relation if necessary
        if ($serieLocalizedName->getSerie() !== $this) {
            $serieLocalizedName->setSerie($this);
        }

        $this->serieLocalizedName = $serieLocalizedName;

        return $this;
    }

    public function getOriginCountry(): array
    {
        return $this->originCountry;
    }

    public function setOriginCountry(array $originCountry): static
    {
        $this->originCountry = $originCountry;

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode): static
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->setSeries($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): static
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getSeries() === $this) {
                $episode->setSeries(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSeries($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): static
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSeries() === $this) {
                $season->setSeries(null);
            }
        }

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

    /**
     * @return Collection<int, SerieAlternateOverview>
     */
    public function getSeriesAlternateOverviews(): Collection
    {
        return $this->seriesAlternateOverviews;
    }

    public function addSeriesAlternateOverview(SerieAlternateOverview $seriesAlternateOverview): static
    {
        if (!$this->seriesAlternateOverviews->contains($seriesAlternateOverview)) {
            $this->seriesAlternateOverviews->add($seriesAlternateOverview);
            $seriesAlternateOverview->setSeries($this);
        }

        return $this;
    }

    public function removeSeriesAlternateOverview(SerieAlternateOverview $seriesAlternateOverview): static
    {
        if ($this->seriesAlternateOverviews->removeElement($seriesAlternateOverview)) {
            // set the owning side to null (unless already changed)
            if ($seriesAlternateOverview->getSeries() === $this) {
                $seriesAlternateOverview->setSeries(null);
            }
        }

        return $this;
    }
}

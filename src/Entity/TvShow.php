<?php

namespace App\Entity;

use App\Repository\TvShowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TvShowRepository::class)]
class TvShow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $adult;

    #[ORM\ManyToMany(targetEntity: Creator::class, inversedBy: 'tvShows')]
    private $created_by;

    #[ORM\Column(type: 'array', nullable: true)]
    private $episode_run_time = [];

    #[ORM\Column(type: 'date', nullable: true)]
    private $first_air_date;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'tvShows')]
    private $genres;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $homepage;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $tv_show_id;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $in_production;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private $languages = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\Column(type: 'date', nullable: true)]
    private $last_air_date;

    #[ORM\OneToOne(targetEntity: TvEpisodeToAir::class, cascade: ['persist', 'remove'])]
    private $last_episode_to_air;

    #[ORM\OneToOne(targetEntity: TvEpisodeToAir::class, cascade: ['persist', 'remove'])]
    private $next_episode_to_air;

    #[ORM\ManyToMany(targetEntity: Network::class, inversedBy: 'tvShows')]
    private $networks;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $number_of_episodes;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $number_of_seasons;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private $origin_country = [];

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $original_language;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $original_name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $overview;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $popularity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $poster_path;

    #[ORM\ManyToMany(targetEntity: Company::class, inversedBy: 'tv_shows')]
    private $production_companies;

    #[ORM\ManyToMany(targetEntity: ProductionCountry::class, inversedBy: 'tvShows')]
    private $production_countries;

    #[ORM\OneToMany(mappedBy: 'tvShow', targetEntity: TvSeason::class)]
    private $seasons;

    #[ORM\ManyToMany(targetEntity: SpokenLanguage::class, inversedBy: 'tv_shows')]
    private $spoken_languages;

    #[ORM\ManyToMany(targetEntity: Status::class, inversedBy: 'tvShows')]
    private $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private $tagline;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $tv_show_type;

    #[ORM\Column(type: 'float', nullable: true)]
    private $vote_average;

    #[ORM\Column(type: 'integer')]
    private $vote_count;

    public function __construct()
    {
        $this->created_by = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->networks = new ArrayCollection();
        $this->production_companies = new ArrayCollection();
        $this->production_countries = new ArrayCollection();
        $this->seasons = new ArrayCollection();
        $this->spoken_languages = new ArrayCollection();
        $this->status = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Creator>
     */
    public function getCreatedBy(): Collection
    {
        return $this->created_by;
    }

    public function addCreatedBy(Creator $createdBy): self
    {
        if (!$this->created_by->contains($createdBy)) {
            $this->created_by[] = $createdBy;
        }

        return $this;
    }

    public function removeCreatedBy(Creator $createdBy): self
    {
        $this->created_by->removeElement($createdBy);

        return $this;
    }

    public function getEpisodeRunTime(): ?array
    {
        return $this->episode_run_time;
    }

    public function setEpisodeRunTime(?array $episode_run_time): self
    {
        $this->episode_run_time = $episode_run_time;

        return $this;
    }

    public function getFirstAirDate(): ?\DateTimeInterface
    {
        return $this->first_air_date;
    }

    public function setFirstAirDate(?\DateTimeInterface $first_air_date): self
    {
        $this->first_air_date = $first_air_date;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): self
    {
        if (!$this->genres->contains($genre)) {
            $this->genres[] = $genre;
        }

        return $this;
    }

    public function removeGenre(Genre $genre): self
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): self
    {
        $this->homepage = $homepage;

        return $this;
    }

    public function getTvShowId(): ?int
    {
        return $this->tv_show_id;
    }

    public function setTvShowId(?int $tv_show_id): self
    {
        $this->tv_show_id = $tv_show_id;

        return $this;
    }

    public function getInProduction(): ?bool
    {
        return $this->in_production;
    }

    public function setInProduction(?bool $in_production): self
    {
        $this->in_production = $in_production;

        return $this;
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

    public function getNextEpisodeToAir(): ?TvEpisodeToAir
    {
        return $this->next_episode_to_air;
    }

    public function setNextEpisodeToAir(?TvEpisodeToAir $next_episode_to_air): self
    {
        $this->next_episode_to_air = $next_episode_to_air;

        return $this;
    }

    /**
     * @return Collection<int, Network>
     */
    public function getNetworks(): Collection
    {
        return $this->networks;
    }

    public function addNetwork(Network $network): self
    {
        if (!$this->networks->contains($network)) {
            $this->networks[] = $network;
        }

        return $this;
    }

    public function removeNetwork(Network $network): self
    {
        $this->networks->removeElement($network);

        return $this;
    }

    public function getNumberOfEpisodes(): ?int
    {
        return $this->number_of_episodes;
    }

    public function setNumberOfEpisodes(?int $number_of_episodes): self
    {
        $this->number_of_episodes = $number_of_episodes;

        return $this;
    }

    public function getNumberOfSeasons(): ?int
    {
        return $this->number_of_seasons;
    }

    public function setNumberOfSeasons(?int $number_of_seasons): self
    {
        $this->number_of_seasons = $number_of_seasons;

        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): self
    {
        $this->languages = $languages;

        return $this;
    }

    public function getOriginCountry(): ?array
    {
        return $this->origin_country;
    }

    public function setOriginCountry(?array $origin_country): self
    {
        $this->origin_country = $origin_country;

        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->original_language;
    }

    public function setOriginalLanguage(?string $original_language): self
    {
        $this->original_language = $original_language;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->original_name;
    }

    public function setOriginalName(?string $original_name): self
    {
        $this->original_name = $original_name;

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

    public function getPopularity(): ?int
    {
        return $this->popularity;
    }

    public function setPopularity(?int $popularity): self
    {
        $this->popularity = $popularity;

        return $this;
    }

    public function getPosterPath(): ?string
    {
        return $this->poster_path;
    }

    public function setPosterPath(?string $poster_path): self
    {
        $this->poster_path = $poster_path;

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getProductionCompanies(): Collection
    {
        return $this->production_companies;
    }

    public function addProductionCompany(Company $productionCompany): self
    {
        if (!$this->production_companies->contains($productionCompany)) {
            $this->production_companies[] = $productionCompany;
        }

        return $this;
    }

    public function removeProductionCompany(Company $productionCompany): self
    {
        $this->production_companies->removeElement($productionCompany);

        return $this;
    }

    /**
     * @return Collection<int, ProductionCountry>
     */
    public function getProductionCountries(): Collection
    {
        return $this->production_countries;
    }

    public function addProductionCountry(ProductionCountry $productionCountry): self
    {
        if (!$this->production_countries->contains($productionCountry)) {
            $this->production_countries[] = $productionCountry;
        }

        return $this;
    }

    public function removeProductionCountry(ProductionCountry $productionCountry): self
    {
        $this->production_countries->removeElement($productionCountry);

        return $this;
    }

    /**
     * @return Collection<int, TvSeason>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(TvSeason $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons[] = $season;
            $season->setTvShow($this);
        }

        return $this;
    }

    public function removeSeason(TvSeason $season): self
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getTvShow() === $this) {
                $season->setTvShow(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SpokenLanguage>
     */
    public function getSpokenLanguages(): Collection
    {
        return $this->spoken_languages;
    }

    public function addSpokenLanguage(SpokenLanguage $spokenLanguage): self
    {
        if (!$this->spoken_languages->contains($spokenLanguage)) {
            $this->spoken_languages[] = $spokenLanguage;
        }

        return $this;
    }

    public function removeSpokenLanguage(SpokenLanguage $spokenLanguage): self
    {
        $this->spoken_languages->removeElement($spokenLanguage);

        return $this;
    }

    /**
     * @return Collection<int, Status>
     */
    public function getStatus(): Collection
    {
        return $this->status;
    }

    public function addStatus(Status $status): self
    {
        if (!$this->status->contains($status)) {
            $this->status[] = $status;
        }

        return $this;
    }

    public function removeStatus(Status $status): self
    {
        $this->status->removeElement($status);

        return $this;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): self
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getTvShowType(): ?string
    {
        return $this->tv_show_type;
    }

    public function setTvShowType(?string $tv_show_type): self
    {
        $this->tv_show_type = $tv_show_type;

        return $this;
    }

    public function getVoteAverage(): ?float
    {
        return $this->vote_average;
    }

    public function setVoteAverage(?float $vote_average): self
    {
        $this->vote_average = $vote_average;

        return $this;
    }

    public function getVoteCount(): ?int
    {
        return $this->vote_count;
    }

    public function setVoteCount(int $vote_count): self
    {
        $this->vote_count = $vote_count;

        return $this;
    }

    public function getAdult(): ?bool
    {
        return $this->adult;
    }

    public function setAdult(?bool $adult): self
    {
        $this->adult = $adult;

        return $this;
    }

    public function getLastAirDate(): ?\DateTimeInterface
    {
        return $this->last_air_date;
    }

    public function setLastAirDate(?\DateTimeInterface $last_air_date): self
    {
        $this->last_air_date = $last_air_date;

        return $this;
    }

    public function getLastEpisodeToAir(): ?TvEpisodeToAir
    {
        return $this->last_episode_to_air;
    }

    public function setLastEpisodeToAir(?TvEpisodeToAir $last_episode_to_air): self
    {
        $this->last_episode_to_air = $last_episode_to_air;

        return $this;
    }
}

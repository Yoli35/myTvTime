<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $adult;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $backdrop_path;

    #[ORM\ManyToMany(targetEntity: MovieCollection::class, inversedBy: 'movies')]
    private Collection $belongs_to_collection;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $budget;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies')]
    private Collection $genres;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $homepage;

    #[ORM\Column(type: 'integer')]
    private ?int $movie_db_id;

    #[ORM\Column(type: 'string', length: 9, nullable: true)]
    private string $imdb_id;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private string $original_language;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $original_title;

    #[ORM\Column(type: 'text', nullable: true)]
    private string $overview;

    #[ORM\Column(type: 'float', nullable: true)]
    private float $popularity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $poster_path;

    #[ORM\ManyToMany(targetEntity: Company::class, inversedBy: 'movies')]
    private Collection $production_companies;

    #[ORM\ManyToMany(targetEntity: ProductionCountry::class, inversedBy: 'movies')]
    private Collection $production_countries;

    #[ORM\Column(type: 'date', nullable: true)]
    private DateTimeInterface $release_date;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $revenue;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $runtime;

    #[ORM\ManyToMany(targetEntity: SpokenLanguage::class, inversedBy: 'movies')]
    private Collection $spoken_languages;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $tagline;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $title;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $video;

    #[ORM\Column(type: 'float', nullable: true)]
    private float $vote_average;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $vote_count;

    #[ORM\ManyToOne(targetEntity: Status::class, inversedBy: 'movies')]
    private Status $status;

    #[Pure] public function __construct()
    {
        $this->genres = new ArrayCollection();
        $this->production_companies = new ArrayCollection();
        $this->production_countries = new ArrayCollection();
        $this->spoken_languages = new ArrayCollection();
        $this->belongs_to_collection = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBackdropPath(): ?string
    {
        return $this->backdrop_path;
    }

    public function setBackdropPath(?string $backdrop_path): self
    {
        $this->backdrop_path = $backdrop_path;

        return $this;
    }

    public function getBudget(): ?int
    {
        return $this->budget;
    }

    public function setBudget(?int $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @return Collection|Genre[]
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

    public function getImdbId(): ?string
    {
        return $this->imdb_id;
    }

    public function setImdbId(?string $imdb_id): self
    {
        $this->imdb_id = $imdb_id;

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

    public function getOriginalTitle(): ?string
    {
        return $this->original_title;
    }

    public function setOriginalTitle(?string $original_title): self
    {
        $this->original_title = $original_title;

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

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): self
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
     * @return Collection|Company[]
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
     * @return Collection|ProductionCountry[]
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

    public function getReleaseDate(): ?DateTimeInterface
    {
        return $this->release_date;
    }

    public function setReleaseDate(?DateTimeInterface $release_date): self
    {
        $this->release_date = $release_date;

        return $this;
    }

    public function getRevenue(): ?int
    {
        return $this->revenue;
    }

    public function setRevenue(?int $revenue): self
    {
        $this->revenue = $revenue;

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): self
    {
        $this->runtime = $runtime;

        return $this;
    }

    /**
     * @return Collection|SpokenLanguage[]
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

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function setTagline(?string $tagline): self
    {
        $this->tagline = $tagline;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getVideo(): ?bool
    {
        return $this->video;
    }

    public function setVideo(?bool $video): self
    {
        $this->video = $video;

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

    public function setVoteCount(?int $vote_count): self
    {
        $this->vote_count = $vote_count;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getMovieDbId(): ?int
    {
        return $this->movie_db_id;
    }

    public function setMovieDbId(int $movie_db_id): self
    {
        $this->movie_db_id = $movie_db_id;

        return $this;
    }

    /**
     * @return Collection<int, MovieCollection>
     */
    public function getBelongsToCollection(): Collection
    {
        return $this->belongs_to_collection;
    }

    public function addBelongsToCollection(MovieCollection $belongsToCollection): self
    {
        if (!$this->belongs_to_collection->contains($belongsToCollection)) {
            $this->belongs_to_collection[] = $belongsToCollection;
        }

        return $this;
    }

    public function removeBelongsToCollection(MovieCollection $belongsToCollection): self
    {
        $this->belongs_to_collection->removeElement($belongsToCollection);

        return $this;
    }
}

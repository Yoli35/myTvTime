<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $company_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $logo_path;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $origin_country;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private self $parent_company;

    #[ORM\Column(type: 'text', nullable: true)]
    private string $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $headquarters;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $homepage;

    #[ORM\ManyToMany(targetEntity: Movie::class, mappedBy: 'production_companies')]
    private Collection $movies;

    #[ORM\ManyToMany(targetEntity: TvShow::class, mappedBy: 'production_companies')]
    private $tv_shows;

    #[Pure] public function __construct()
    {
        $this->movies = new ArrayCollection();
        $this->tv_shows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogoPath(): ?string
    {
        return $this->logo_path;
    }

    public function setLogoPath(?string $logo_path): self
    {
        $this->logo_path = (string)$logo_path;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOriginCountry(): ?string
    {
        return $this->origin_country;
    }

    public function setOriginCountry(string $origin_country): self
    {
        $this->origin_country = $origin_country;

        return $this;
    }

    public function getParentCompany(): ?self
    {
        return $this->parent_company;
    }

    public function setParentCompany(?self $parent_company): self
    {
        $this->parent_company = $parent_company;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getHeadquarters(): ?string
    {
        return $this->headquarters;
    }

    public function setHeadquarters(?string $headquarters): self
    {
        $this->headquarters = $headquarters;

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

    /**
     * @return Collection|Movie[]
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies[] = $movie;
            $movie->addProductionCompany($this);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): self
    {
        if ($this->movies->removeElement($movie)) {
            $movie->removeProductionCompany($this);
        }

        return $this;
    }

    public function getCompanyId(): ?int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): self
    {
        $this->company_id = $company_id;

        return $this;
    }

    /**
     * @return Collection<int, TvShow>
     */
    public function getTvShows(): Collection
    {
        return $this->tv_shows;
    }

    public function addTvShow(TvShow $tvShow): self
    {
        if (!$this->tv_shows->contains($tvShow)) {
            $this->tv_shows[] = $tvShow;
            $tvShow->addProductionCompany($this);
        }

        return $this;
    }

    public function removeTvShow(TvShow $tvShow): self
    {
        if ($this->tv_shows->removeElement($tvShow)) {
            $tvShow->removeProductionCompany($this);
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SpokenLanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

#[ORM\Entity(repositoryClass: SpokenLanguageRepository::class)]
class SpokenLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $english_name;

    #[ORM\Column(type: 'string', length: 16)]
    private string $iso_639_1;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Movie::class, mappedBy: 'spoken_languages')]
    private Collection $movies;

    #[ORM\ManyToMany(targetEntity: TvShow::class, mappedBy: 'spoken_languages')]
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

    public function getEnglishName(): ?string
    {
        return $this->english_name;
    }

    public function setEnglishName(?string $english_name): self
    {
        $this->english_name = $english_name;

        return $this;
    }

    public function getIso6391(): ?string
    {
        return $this->iso_639_1;
    }

    public function setIso6391(string $iso_639_1): self
    {
        $this->iso_639_1 = $iso_639_1;

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
            $movie->addSpokenLanguage($this);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): self
    {
        if ($this->movies->removeElement($movie)) {
            $movie->removeSpokenLanguage($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, TvShow>
     */
    public function getTvShows(): Collection
    {
        return $this->tv_shows;
    }

    public function addTvShow(TvShow $tv_show): self
    {
        if (!$this->tv_shows->contains($tv_show)) {
            $this->tv_shows[] = $tv_show;
            $tv_show->addSpokenLanguage($this);
        }

        return $this;
    }

    public function removeTv(TvShow $tv_show): self
    {
        if ($this->tv_shows->removeElement($tv_show)) {
            $tv_show->removeSpokenLanguage($this);
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\TvSeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TvSeasonRepository::class)]
class TvSeason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $_id;

    #[ORM\Column(type: 'date', nullable: true)]
    private $air_date;

    #[ORM\OneToMany(mappedBy: 'tvSeason', targetEntity: TvEpisode::class)]
    private $episodes;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $overview;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $season_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $poster_path;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $season_number;

    #[ORM\ManyToOne(targetEntity: TvShow::class, inversedBy: 'seasons')]
    private $tvShow;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $episode_count;

    public function __construct()
    {
        $this->episodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?string $_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    public function getAirDate(): ?\DateTimeInterface
    {
        return $this->air_date;
    }

    public function setAirDate(?\DateTimeInterface $air_date): self
    {
        $this->air_date = $air_date;

        return $this;
    }

    /**
     * @return Collection<int, TvEpisode>
     */
    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(TvEpisode $episode): self
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes[] = $episode;
            $episode->setTvSeason($this);
        }

        return $this;
    }

    public function removeEpisode(TvEpisode $episode): self
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getTvSeason() === $this) {
                $episode->setTvSeason(null);
            }
        }

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

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;

        return $this;
    }

    public function getSeasonId(): ?int
    {
        return $this->season_id;
    }

    public function setSeasonId(?int $season_id): self
    {
        $this->season_id = $season_id;

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

    public function getSeasonNumber(): ?int
    {
        return $this->season_number;
    }

    public function setSeasonNumber(?int $season_number): self
    {
        $this->season_number = $season_number;

        return $this;
    }

    public function getTvShow(): ?TvShow
    {
        return $this->tvShow;
    }

    public function setTvShow(?TvShow $tvShow): self
    {
        $this->tvShow = $tvShow;

        return $this;
    }

    public function getEpisodeCount(): ?int
    {
        return $this->episode_count;
    }

    public function setEpisodeCount(?int $episode_count): self
    {
        $this->episode_count = $episode_count;

        return $this;
    }
}

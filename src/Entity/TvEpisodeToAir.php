<?php

namespace App\Entity;

use App\Repository\TvEpisodeToAirRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TvEpisodeToAirRepository::class)]
class TvEpisodeToAir
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date', nullable: true)]
    private $air_date;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $episode_number;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $episode_to_air_id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $overview;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $production_code;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $season_number;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $still_path;

    #[ORM\Column(type: 'float', nullable: true)]
    private $vote_average;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $vote_count;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEpisodeNumber(): ?int
    {
        return $this->episode_number;
    }

    public function setEpisodeNumber(?int $episode_number): self
    {
        $this->episode_number = $episode_number;

        return $this;
    }

    public function getEpisodeToAirId(): ?int
    {
        return $this->episode_to_air_id;
    }

    public function setEpisodeToAirId(?int $episode_to_air_id): self
    {
        $this->episode_to_air_id = $episode_to_air_id;

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

    public function getProductionCode(): ?string
    {
        return $this->production_code;
    }

    public function setProductionCode(?string $production_code): self
    {
        $this->production_code = $production_code;

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

    public function getStillPath(): ?string
    {
        return $this->still_path;
    }

    public function setStillPath(?string $still_path): self
    {
        $this->still_path = $still_path;

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
}

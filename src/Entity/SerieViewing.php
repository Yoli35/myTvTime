<?php

namespace App\Entity;

use App\Repository\SerieViewingRepository;
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

    #[ORM\Column]
    private array $viewing = [];

    #[ORM\Column(nullable: true)]
    private ?int $viewedEpisodes = null;

    #[ORM\Column]
    private ?bool $specialEpisodes = false;

    #[ORM\Column]
    private ?int $seasonCount = null;

    #[ORM\OneToMany(mappedBy: 'serie', targetEntity: SeasonViewing::class)]
    private Collection $seasons; /* will replace viewing */

    public function __construct()
    {
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

    public function getViewing(): array
    {
        return $this->viewing;
    }

    public function setViewing(array $viewing): self
    {
        $this->viewing = $viewing;

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

    public function addSeason(SeasonViewing $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
            $season->setSerie($this);
        }

        return $this;
    }

    public function removeSeason(SeasonViewing $season): self
    {
        if ($this->seasons->removeElement($season)) {
            // set the owning side to null (unless already changed)
            if ($season->getSerie() === $this) {
                $season->setSerie(null);
            }
        }

        return $this;
    }
}

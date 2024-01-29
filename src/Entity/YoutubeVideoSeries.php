<?php

namespace App\Entity;

use App\Repository\YoutubeVideoSeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeVideoSeriesRepository::class)]
class YoutubeVideoSeries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $format = null;

    #[ORM\Column]
    private ?bool $regex = null;

    #[ORM\Column(nullable: true)]
    private ?array $matches = null;

    private Collection $matchesCollection; // Pour le formulaire Nouvelle serie

    #[ORM\OneToMany(mappedBy: 'series', targetEntity: UserYVideo::class)]
    private Collection $userYVideos;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?int $serieId = null;

    public function __construct()
    {
        $this->matchesCollection = new ArrayCollection();
        $this->userYVideos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function isRegex(): ?bool
    {
        return $this->regex;
    }

    public function setRegex(bool $regex): static
    {
        $this->regex = $regex;

        return $this;
    }

    public function getMatches(): ?array
    {
        return $this->matches;
    }

    public function setMatches(?array $matches): static
    {
        $this->matches = $matches;

        return $this;
    }

    /**
     * @return Collection<int, UserYVideo>
     */
    public function getUserYVideos(): Collection
    {
        return $this->userYVideos;
    }

    public function addUserYVideo(UserYVideo $userYVideo): static
    {
        if (!$this->userYVideos->contains($userYVideo)) {
            $this->userYVideos->add($userYVideo);
            $userYVideo->setSeries($this);
        }

        return $this;
    }

    public function removeUserYVideo(UserYVideo $userYVideo): static
    {
        if ($this->userYVideos->removeElement($userYVideo)) {
            // set the owning side to null (unless already changed)
            if ($userYVideo->getSeries() === $this) {
                $userYVideo->setSeries(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMatchesCollection(): Collection
    {
        return $this->matchesCollection;
    }

    public function addMatch(VideoSeriesMatch $match): static
    {
        if (!$this->matchesCollection->contains($match)) {
            $this->matchesCollection->add($match);
        }

        return $this;
    }

    public function removeMatch(VideoSeriesMatch $match): static
    {
        $this->matchesCollection->removeElement($match);

        return $this;
    }

    public function getSerieId(): ?int
    {
        return $this->serieId;
    }

    public function setSerieId(?int $serieId): static
    {
        $this->serieId = $serieId;

        return $this;
    }
}

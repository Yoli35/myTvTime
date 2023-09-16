<?php

namespace App\Entity;

use App\Repository\EpisodeViewingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: EpisodeViewingRepository::class)]
class EpisodeViewing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $episodeNumber;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SeasonViewing $season = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $networkType = null;

    #[ORM\Column(nullable: true)]
    private ?int $networkId = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $deviceType = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $viewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $airDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substituteName = null;

    // Entre 0 et 10
    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?int $vote = 0;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?int $numberOfView = null;

    public function __construct($episodeNumber, $season, $airDate)
    {
        $this->episodeNumber = $episodeNumber;
        $this->season = $season;
        if ($airDate) {
            try {
                $this->airDate = new DateTimeImmutable($airDate);
            } catch (Exception) {
                $this->airDate = null;
            }
        }
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): self
    {
        $this->episodeNumber = $episodeNumber;

        return $this;
    }

    public function getSeason(): ?SeasonViewing
    {
        return $this->season;
    }

    public function setSeason(?SeasonViewing $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getNetworkType(): ?string
    {
        return $this->networkType;
    }

    public function setNetworkType(?string $networkType): self
    {
        $this->networkType = $networkType;

        return $this;
    }

    public function getNetworkId(): ?int
    {
        return $this->networkId;
    }

    public function setNetworkId(?int $networkId): self
    {
        $this->networkId = $networkId;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): self
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getViewedAt(): ?DateTimeImmutable
    {
        return $this->viewedAt;
    }

    public function setViewedAt(?DateTimeImmutable $viewedAt): self
    {
        $this->viewedAt = $viewedAt;

        return $this;
    }

    public function isViewed(): bool
    {
        return $this->viewedAt !== null;
    }

    public function getAirDate(): ?DateTimeImmutable
    {
        return $this->airDate;
    }

    public function setAirDate(?DateTimeImmutable $airDate): self
    {
        $this->airDate = $airDate;

        return $this;
    }

    public function getSubstituteName(): ?string
    {
        return $this->substituteName;
    }

    public function setSubstituteName(?string $substituteName): self
    {
        $this->substituteName = $substituteName;

        return $this;
    }

    public function getVote(): ?int
    {
        return $this->vote;
    }

    public function setVote(?int $vote): static
    {
        $this->vote = $vote;

        return $this;
    }

    public function getNumberOfView(): ?int
    {
        return $this->numberOfView;
    }

    public function setNumberOfView(?int $numberOfView): static
    {
        $this->numberOfView = $numberOfView;

        return $this;
    }
}

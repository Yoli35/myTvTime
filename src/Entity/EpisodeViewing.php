<?php

namespace App\Entity;

use App\Repository\EpisodeViewingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EpisodeViewingRepository::class)]
class EpisodeViewing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $episodeNumber = null;

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
    private ?\DateTimeImmutable $viewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $airDate = null;

    public function __construct($episodeNumber)
    {
        $this->episodeNumber = $episodeNumber;
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

    public function getViewedAt(): ?\DateTimeImmutable
    {
        return $this->viewedAt;
    }

    public function setViewedAt(?\DateTimeImmutable $viewedAt): self
    {
        $this->viewedAt = $viewedAt;

        return $this;
    }

    public function getAirDate(): ?\DateTimeImmutable
    {
        return $this->airDate;
    }

    public function setAirDate(?\DateTimeImmutable $airDate): self
    {
        $this->airDate = $airDate;

        return $this;
    }
}
<?php

namespace App\Entity;

use App\Repository\SerieAlternateOverviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieAlternateOverviewRepository::class)]
class SerieAlternateOverview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'seriesAlternateOverviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $series = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $overview = null;

    #[ORM\Column(length: 2)]
    private ?string $locale = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?array $overviews = null;

    public function __construct($series, $locale, $overview)
    {
        $this->setSeries($series);
        $this->setLocale($locale);
        $this->setOverview($overview);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(string $overview): static
    {
        $this->overview = $overview;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSeries(): ?Serie
    {
        return $this->series;
    }

    public function setSeries(?Serie $series): static
    {
        $this->series = $series;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getOverviews(): ?array
    {
        return $this->overviews;
    }

    public function setOverviews(?array $overviews): static
    {
        $this->overviews = $overviews;

        return $this;
    }
}

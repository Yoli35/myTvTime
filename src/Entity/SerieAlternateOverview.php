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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $overview = null;

    #[ORM\Column(length: 2)]
    private ?string $locale = null;

    #[ORM\ManyToOne(cascade: ['persist', 'remove'], inversedBy: 'seriesAlternateOverviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $series = null;

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
}

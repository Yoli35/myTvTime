<?php

namespace App\Twig;

use App\Config\FavoriteType;
use App\Entity\Serie;
use App\Entity\SerieViewing;
use App\Repository\FavoriteRepository;
use App\Repository\SerieViewingRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AfterwardsExtension extends AbstractExtension
{
    public function __construct(private readonly SerieViewingRepository $repository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_afterwards', [$this, 'isAfterwards']),
        ];
    }

    public function isAfterwards(SerieViewing $serie): bool
    {
        $interval = [];
        foreach ($serie->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getViewedAt()) {
                    $interval[] = date_diff($episode->getAirDate(), $episode->getViewedAt());
                }
            }
        }
        return false;
    }
}

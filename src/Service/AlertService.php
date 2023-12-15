<?php

namespace App\Service;

use App\Repository\AlertRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AlertService extends AbstractController
{
    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly ImageConfiguration $imageConfiguration,
    )
    {

    }

    public function checkUserAlertsOfTheDay($user, $from): void
    {
        $alerts = $this->alertRepository->alertOfTheDay($user->getId());

        $from = $from ? '?from=' . $from : '';

        foreach ($alerts as $alert) {
            $this->addFlash('alert', [
                'alert_id' => $alert['id'],
                'href' => $this->generateUrl('app_series_tmdb_season', ['id' => $alert['tmdb_id'], 'seasonNumber' => $alert['alert_season_number']]) . $from,
                'message' => $alert['message'],
                'provider_id' => $alert['provider_id'],
                'alert_episode_number' => $alert['alert_episode_number'],
                'alert_season_number' => $alert['alert_season_number'],
                'number_of_episodes' => $alert['number_of_episodes'],
                'number_of_seasons' => $alert['number_of_seasons'],
                'viewed_episodes' => $alert['viewed_episodes'],
                'name' => $alert['name'],
                'original_name' => $alert['original_name'],
                'localized_name' => $alert['localized_name'],
                'season_poster_path' => $alert['season_poster_path'] ? $this->imageConfiguration->getCompleteUrl($alert['season_poster_path'], 'poster_sizes', 2) : null,
                'episode_still_path' => $alert['episode_still_path'] ? $this->imageConfiguration->getCompleteUrl($alert['episode_still_path'], 'still_sizes', 2) : null,
            ]);
        }
    }
}

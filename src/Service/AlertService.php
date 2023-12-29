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

        if (count($alerts) === 0) {
            return;
        }
        $from = $from ? '?from=' . $from : '';

        foreach ($alerts as $alert) {
            $countries = preg_match('/[A-Z]{2}/', $alert['origin_country_array'], $matches);
//          dump($matches);
            $this->addFlash('alert', [
                'alert_id' => $alert['id'],
                'href' => $this->generateUrl('app_series_tmdb_season', ['id' => $alert['tmdb_id'], 'seasonNumber' => $alert['alert_season_number']]) . $from,
                'direct_link' => $alert['direct_link'],
                'country_page' => $this->generateUrl('app_series_from_country', ['countryCode' => $matches[0]]) . $from,
                'message' => $alert['message'],
                'provider_id' => $alert['provider_id'],
                'alert_episode_number' => $alert['alert_episode_number'],
                'alert_season_number' => $alert['alert_season_number'],
                'number_of_episodes' => $alert['number_of_episodes'],
                'number_of_seasons' => $alert['number_of_seasons'],
                'viewed_episodes' => $alert['viewed_episodes'],
                'name' => $alert['name'],
                'origin_country_array' => $countries ? $matches : null,
                'original_name' => $alert['original_name'],
                'localized_name' => $alert['localized_name'],
                'season_poster_path' => $alert['season_poster_path'] ? $this->imageConfiguration->getCompleteUrl($alert['season_poster_path'], 'poster_sizes', 3) : null,
                'episode_still_path' => $alert['episode_still_path'] ? $this->imageConfiguration->getCompleteUrl($alert['episode_still_path'], 'still_sizes', 3) : null,
                'provider_name' => $alert['provider_name'],
                'provider_logo_path' => $alert['provider_logo_path'] ? $this->imageConfiguration->getCompleteUrl($alert['provider_logo_path'], 'logo_sizes', 3) : null,
            ]);
        }
    }
}

<?php

namespace App\Controller;

use App\Service;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TvController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/tv', name: 'app_home_tv', requirements: ['_locale' => 'fr|en|de|es'])]
    public function indexTv(Request $request, Service\CallTmdbService $callTmdbService, HomeController $homeController, ManagerRegistry $doctrine): Response
    {
        $page = $request->query->getInt('page', 1);
        $sort_by = $request->query->get('sort', 'popularity.desc');
        $locale = $request->getLocale();

        $sorts = [
            'sort_by' => $sort_by,
            'options' => [
                'Descending Vote Average' => 'vote_average.desc',
                'Ascending Vote Average' => 'vote_average.asc',
                'Descending First Air Date' => 'first_air_date.desc',
                'Ascending First Air Date' => 'first_air_date.asc',
                'Descending Popularity' => 'popularity.desc',
                'Ascending Popularity' => 'popularity.asc'
            ]
        ];

        $standing = $callTmdbService->discoverTv($page, $locale);
        $discovers = json_decode($standing, true, 512, 0);
        $imageConfig = $homeController->getImageConfig($doctrine);
        $pages = ['page' => $discovers['page'], 'total_pages' => $discovers['total_pages'], 'total_results' => $discovers['total_results']];

        return $this->render('home/index.html.twig', [
            'discovers' => $discovers,
            'imageConfig' => $imageConfig,
            'pages' => $pages,
            'sorts' => $sorts,
            'dRoute' => 'app_tv',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/tv/{id}', name: 'app_tv', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['id' => 15766])]
    public function index(Request $request, $id, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController): Response
    {
        $locale = $request->getLocale();
        $standing = $callTmdbService->getTv($id, $locale);
        $tv = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getTvCredits($id, $locale);
        $credits = json_decode($standing, true);
        $imageConfig = $homeController->getImageConfig($doctrine);

        $cast = $credits['cast'];
        $crew = $credits['crew'];
//        $crew = usort($credits['crew'], "memberCmp");

        return $this->render('tv/index.html.twig', [
            'tv' => $tv,
            'cast' => $cast,
            'crew' => $crew,
            'locale' => $locale,
            'imageConfig' => $imageConfig,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/tv/season/{id}/{season_number}', name: 'app_season', requirements: ['_locale' => 'fr|en|de|es'])]
    public function season(Request $request, $id, $season_number, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController):Response
    {
        $locale = $request->getLocale();
        $standing = $callTmdbService->getTvSeason($id, $season_number, $locale);
        $season = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getTv($id, $locale);
        $tv = json_decode($standing, true, 512, 0);
        $imageConfig = $homeController->getImageConfig($doctrine);

        return $this->render('tv/season.html.twig', [
            'season' => $season,
            'tv' => $tv['name'],
            'tv_id' => $tv['id'],
            'imageConfig' => $imageConfig,]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/tv/episode/{id}/{season_number}/{episode_number}', name: 'app_episode', requirements: ['_locale' => 'fr|en|de|es'])]
    public function episode(Request $request, $id, $season_number, $episode_number, ManagerRegistry $doctrine, Service\CallTmdbService $callTmdbService, HomeController $homeController):Response
    {
        $locale = $request->getLocale();
        $standing = $callTmdbService->getTvSeason($id, $season_number, $locale);
        $season = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getTvEpisode($id, $season_number, $episode_number, $locale);
        $episode = json_decode($standing, true, 512, 0);
        $standing = $callTmdbService->getTv($id, $locale);
        $tv = json_decode($standing, true, 512, 0);
        $imageConfig = $homeController->getImageConfig($doctrine);

        return $this->render('tv/episode.html.twig', [
            'season' => $season,
            'episode' => $episode,
            'tv' => ['name' => $tv['name'], 'id' => $tv['id'] ],
            'imageConfig' => $imageConfig,]);
    }
}

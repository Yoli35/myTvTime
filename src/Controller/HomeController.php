<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SerieRepository;

//use App\Repository\SerieViewingRepository;
use App\Repository\MovieRepository;
use App\Service\LogService;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly TMDBService        $TMDBService,
                                private readonly MovieRepository    $userMovieRepository,
                                private readonly SerieRepository    $serieRepository,
//                                private readonly SerieViewingRepository $serieViewingRepository,
                                private readonly FavoriteRepository $favoriteRepository,
                                private readonly ImageConfiguration $imageConfiguration,
                                private readonly LogService         $logService
    )
    {
    }

    #[Route('/', name: 'homeWoLocale')]
    public function home(Request $request): RedirectResponse
    {
        $locale = $request->getLocale();
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }

    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request): Response
    {
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();

        if ($user) {
            $lastAddedMovies = $this->userMovieRepository->lastAddedMovies($user->getId(), 20);
            $lastAddedSeries = $this->serieRepository->lastAddedSeries($user->getId(), 20);
            $lastUpdatedSeries = $this->serieRepository->lastUpdatedSeries($user->getId(), 20);
            $lastWatchedSeries = $this->serieRepository->lastWatchedSeries($user->getId(), 20);

            $favorites = $this->favoriteRepository->findBy(['userId' => $user->getId(), 'type' => 'serie'], ['createdAt' => 'DESC']);
            $favoriteSerieIds = array_map(function ($favorite) {
                return $favorite->getMediaId();
            }, $favorites);
            $favoriteSeries = $this->serieRepository->findBy(['id' => $favoriteSerieIds]/*, ['updatedAt' => 'DESC']*/);

            /*
             * Le critère 'viewedEpisodes' => ['>' => 0] devrait générer la clause
             *    « WHERE viewed_episodes > ? » avec comme paramètre 0
             * mais génère
             *    « WHERE viewed_episodes IN ? » avec comme paramètre 0
             */
//            $lastModifiedSerieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'viewedEpisodes' => ['>' => 0]], ['modifiedAt' => 'DESC'], 20, 0);
//            $lastModifiedSeries = array_map(function ($serieViewing) {
//                return $serieViewing->getSerie();
//            }, $lastModifiedSerieViewings);
//            dump($lastModifiedSeries);

            $favorites = $this->favoriteRepository->findBy(['userId' => $user->getId(), 'type' => 'movie']);
            $favoriteMovieIds = array_map(function ($favorite) {
                return $favorite->getMediaId();
            }, $favorites);
            $favoriteMovies = $this->userMovieRepository->findBy(['id' => $favoriteMovieIds], ['createdAt' => 'DESC']);
        }
        $standing = $this->TMDBService->discoverMovies(1, 'popularity.desc', $locale);
        $popularMovies = json_decode($standing, true);
        $standing = $this->TMDBService->getSeries('popular', 1, $locale);
        $popularSeries = json_decode($standing, true);
        $standing = $this->TMDBService->getPopularPeople($locale);
        $popularPeople = json_decode($standing, true);
        $standing = $this->TMDBService->trending('all', 'day', $locale);
        $trendingOfTheDay = json_decode($standing, true);
        $standing = $this->TMDBService->trending('all', 'week', $locale);
        $trendingOfTheWeek = json_decode($standing, true);

        $imageConfig = $this->imageConfiguration->getConfig();

        return $this->render('home/index.html.twig', [
            'lists' => [
                [
                    'name' => 'last added movies',
                    'data' => $user ? $lastAddedMovies : null,
                    'type' => 'movie',
                ],
                [
                    'name' => 'favorite movies',
                    'data' => $user ? $favoriteMovies : null,
                    'type' => 'movie',
                ],
                [
                    'name' => 'last added series',
                    'data' => $user ? $lastAddedSeries : null,
                    'type' => 'serie',
                ],
                [
                    'name' => 'last modified series',
                    'data' => $user ? $lastWatchedSeries : null,
                    'type' => 'sql serie',
                ],
//                [
//                    'name' => 'last modified series',
//                    'data' => $user ? $lastModifiedSeries : null,
//                    'type' => 'serie',
//                ],
                [
                    'name' => 'last updated series',
                    'data' => $user ? $lastUpdatedSeries : null,
                    'type' => 'serie',
                ],
                [
                    'name' => 'favorite series',
                    'data' => $user ? $favoriteSeries : null,
                    'type' => 'serie',
                ],
                [
                    'name' => 'popular people',
                    'data' => $popularPeople['results'],
                    'type' => 'people',
                ],
                [
                    'name' => 'popular movies',
                    'data' => $popularMovies['results'],
                    'type' => 'tmdb movie',
                ],
                [
                    'name' => 'popular series',
                    'data' => $popularSeries['results'],
                    'type' => 'tmdb serie',
                ],
                [
                    'name' => 'trending today',
                    'data' => $trendingOfTheDay['results'],
                    'type' => 'tmdb mixte',
                ],
                [
                    'name' => 'trending of the week',
                    'data' => $trendingOfTheWeek['results'],
                    'type' => 'tmdb mixte',
                ],
            ],
            'from' => 'home',
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/get-posters', name: 'app_home_get_posters', methods: ['GET'], format: 'json',)]
    public function getRandomPosters(Request $request): Response
    {
        $n = $request->query->getInt('n');
        $time = $n * time();
        srand($time);
        $imageConfig = $this->imageConfiguration->getConfig();
        $movieCount = $this->userMovieRepository->userMoviesCount();
        $movies = $this->userMovieRepository->findBy([], ['createdAt' => 'DESC'], 20, rand(0, $movieCount - 20));
        $serieCount = $this->serieRepository->seriesCount();
        $series = $this->serieRepository->findBy([], ['createdAt' => 'DESC'], 20, rand(0, $serieCount - 20));
        $movies = array_map(function ($movie) use ($imageConfig) {
            return $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $movie->getPosterPath();
        }, $movies);
        $series = array_map(function ($serie) use ($imageConfig) {
            return $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $serie->getPosterPath();
        }, $series);
        $posters = array_merge($movies, $series);
        shuffle($posters);

        return $this->json(['n' => $n, 'time' => $time, 'posters' => $posters]);
    }
}

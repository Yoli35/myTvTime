<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SerieRepository;

use App\Repository\MovieRepository;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly FavoriteRepository $favoriteRepository,
                                private readonly ImageConfiguration $imageConfiguration,
                                private readonly MovieRepository    $movieRepository,
                                private readonly SerieController    $serieController,
                                private readonly SerieRepository    $serieRepository,
                                private readonly TMDBService        $TMDBService,
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
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();

        if ($user) {
            $lastAddedMovies = $this->movieRepository->lastAddedMovies($user->getId(), 20);
            $lastAddedSeries = $this->serieRepository->lastAddedSeries($user->getId(), 20);
            $lastUpdatedSeries = $this->serieRepository->lastUpdatedSeries($user->getId(), 20);
            $lastWatchedSeries = $this->serieRepository->lastWatchedSeries($user->getId(), 20);

            $favorites = $this->favoriteRepository->findBy(['userId' => $user->getId(), 'type' => 'serie'], ['createdAt' => 'DESC']);
            $favoriteSerieIds = array_map(function ($favorite) {
                return $favorite->getMediaId();
            }, $favorites);
            $favoriteSeries = $this->serieRepository->findBy(['id' => $favoriteSerieIds]/*, ['updatedAt' => 'DESC']*/);

            $favorites = $this->favoriteRepository->findBy(['userId' => $user->getId(), 'type' => 'movie']);
            $favoriteMovieIds = array_map(function ($favorite) {
                return $favorite->getMediaId();
            }, $favorites);
            $favoriteMovies = $this->movieRepository->findBy(['id' => $favoriteMovieIds], ['createdAt' => 'DESC']);
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
//        dump([
//            'last added movies' => $user ? $lastAddedMovies:null,
//            'last added series' => $user ? $lastAddedSeries:null,
//            'last updated series' => $user ? $lastUpdatedSeries:null,
//            'last watched series' => $user ? $lastWatchedSeries:null,
//            'favorite series' => $user ? $favoriteSeries:null,
//            'favorite movies' => $user ? $favoriteMovies:null,
//            'popular movies' => $popularMovies,
//            'popular series' => $popularSeries,
//            'popular people' => $popularPeople,
//            'trending of the day' => $trendingOfTheDay,
//            'trending of the week' => $trendingOfTheWeek,
//            ]
//        );

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
                    'type' => 'series',
                ],
                [
                    'name' => 'last modified series',
                    'data' => $user ? $lastWatchedSeries : null,
                    'type' => 'sql series',
                ],
//                [
//                    'name' => 'last modified series',
//                    'data' => $user ? $lastModifiedSeries : null,
//                    'type' => 'series',
//                ],
                [
                    'name' => 'last updated series',
                    'data' => $user ? $lastUpdatedSeries : null,
                    'type' => 'series',
                ],
                [
                    'name' => 'favorite series',
                    'data' => $user ? $favoriteSeries : null,
                    'type' => 'series',
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
                    'type' => 'tmdb series',
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
        $serieController = $this->serieController;
        $imageConfig = $this->imageConfiguration->getConfig();
        $movieCount = $this->movieRepository->moviesCount();
        $movies = $this->movieRepository->findBy([], ['createdAt' => 'DESC'], 20, rand(0, $movieCount - 20));
        $serieCount = $this->serieRepository->seriesCount();
        $series = $this->serieRepository->findBy([], ['createdAt' => 'DESC'], 20, rand(0, $serieCount - 20));
        $movies = array_map(function ($movie) use ($imageConfig, $serieController) {
            $filename = $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $movie->getPosterPath();
            $localName = "../public/images/movies/posters" . $movie->getPosterPath();
            if ($serieController->saveImageFromUrl($filename, $localName)) {
                $filename = "/images/movies/posters" . $movie->getPosterPath();
            }
            return $filename;
        }, $movies);
        $series = array_map(function ($serie) use ($imageConfig, $serieController) {
            $filename = $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $serie->getPosterPath();
            $localName = "../public/images/series/posters" . $serie->getPosterPath();
            if ($serieController->saveImageFromUrl($filename, $localName)) {
                $filename = "/images/series/posters" . $serie->getPosterPath();
            }
            return $filename;
        }, $series);
        $posters = array_merge($movies, $series);
        shuffle($posters);

        return $this->json(['n' => $n, 'time' => $time, 'posters' => $posters]);
    }
}

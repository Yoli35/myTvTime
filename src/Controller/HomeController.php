<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\UserMovieRepository;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(private readonly TMDBService            $TMDBService,
                                private readonly UserMovieRepository    $userMovieRepository,
                                private readonly SerieRepository        $serieRepository,
                                private readonly SerieViewingRepository $serieViewingRepository,
                                private readonly FavoriteRepository     $favoriteRepository,
                                private readonly ImageConfiguration     $imageConfiguration
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
            $lastAddedMovies = $this->userMovieRepository->lastAddedMovies($user->getId(), 20);
            $lastAddedSeries = $this->serieRepository->lastAddedSeries($user->getId(), 20);
            $lastUpdatedSeries = $this->serieRepository->lastUpdatedSeries($user->getId(), 20);

            $favorites = $this->favoriteRepository->findBy(['userId' => $user->getId(), 'type' => 'serie'], ['createdAt' => 'DESC']);
            $favoriteSerieIds = array_map(function ($favorite) {
                return $favorite->getMediaId();
            }, $favorites);
            $favoriteSeries = $this->serieRepository->findBy(['id' => $favoriteSerieIds]/*, ['updatedAt' => 'DESC']*/);

            $lastModifiedSerieViewings = $this->serieViewingRepository->findBy(['user' => $user], ['modifiedAt' => 'DESC'], 20, 0);
            $lastModifiedSeries = array_map(function ($serieViewing) {
                return $serieViewing->getSerie();
            }, $lastModifiedSerieViewings);

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
                    'data' => $user ? $lastModifiedSeries : null,
                    'type' => 'serie',
                ],
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
}

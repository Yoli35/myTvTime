<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SerieRepository;
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
    #[Route('/', name: 'homeWoLocale')]
    public function home(Request $request): RedirectResponse
    {
        $locale = $request->getLocale();
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }

    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, TMDBService $tmdbService, UserMovieRepository $userMovieRepository, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $lastAddedMovies = null;
        $lastUpdatedSeries = null;

        if ($user) {
            $lastAddedMovies = $userMovieRepository->lastAddedMovies($user->getId(), 20);
            $lastUpdatedSeries = $serieRepository->lastUpdatedSeries($user->getId(), 20);
        }
        $standing = $tmdbService->discoverMovies(1, 'popularity.desc', $locale);
        $popularMovies = json_decode($standing, true);
        $standing = $tmdbService->getSeries('popular', 1, $locale);
        $popularSeries = json_decode($standing, true);
        $standing = $tmdbService->getPopularPeople($locale);
        $popularPeople = json_decode($standing, true);
        $standing = $tmdbService->trending('all', 'day', $locale);
        $trending = json_decode($standing, true);
        $imageConfig = $imageConfiguration->getConfig();

        return $this->render('home/index.html.twig', [
            'from' => 'home',
            'lastAddedMovies' => $lastAddedMovies,
            'lastUpdatedSeries' => $lastUpdatedSeries,
            'popularMovies' => $popularMovies,
            'popularSeries' => $popularSeries,
            'popularPeople' => $popularPeople,
            'trending' => $trending,
            'imageConfig' => $imageConfig,
        ]);
    }
}

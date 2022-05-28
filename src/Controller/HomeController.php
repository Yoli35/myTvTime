<?php

namespace App\Controller;

use App\Entity\ImageConfig;
use App\Entity\User;
use App\Entity\UserMovie;
use App\Service;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'homeWoLocale')]
    public function home(Request $request): RedirectResponse
    {
        $locale = $request->getLocale();
//        return $this->redirect('/'.$locale);
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|de|es', 'page'=>1])]
    public function index(Request $request, Service\CallTmdbService $callTmdbService, ManagerRegistry $doctrine): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userMovieIds = [];
        if ($user) {
            $repoUM = $doctrine->getRepository(UserMovie::class);
            $userMovies = $repoUM->findUserMovies($user->getId());
            foreach ($userMovies as $userMovie) {
                $userMovieIds[] = $userMovie['movie_db_id'];
            }
        }
        $page = $request->query->getInt('page', 1);
        $locale = $request->getLocale();
        $standing = $callTmdbService->discoverMovies($page, $locale);
        $discovers = json_decode($standing, true, 512, 0);
        $imageConfig = $this->getImageConfig($doctrine);

        $pages = [
            'page' => $discovers['page'],
            'total_pages' => $discovers['total_pages'],
            'total_results' => $discovers['total_results']
        ];

        return $this->render('home/index.html.twig', [
            'discovers' => $discovers,
            'userMovies' => $userMovieIds,
            'imageConfig' => $imageConfig,
            'pages' => $pages,
            'dRoute' => 'app_movie'
        ]);
    }

    #[ArrayShape(['url' => "string", 'backdrop_sizes' => "array", 'logo_sizes' => "array", 'poster_sizes' => "array", 'profile_sizes' => "array", 'still_sizes' => "array"])]
    public function getImageConfig($doctrine): ?array
    {
        $repoC = $doctrine->getRepository(ImageConfig::class);

        $config = $repoC->findAll();
        $c = $config[0];
        $backdropSizes = $c->getBackdropSizes();
        $logoSizes = $c->getLogoSizes();
        $posterSizes = $c->getPosterSizes();
        $profileSizes = $c->getProfileSizes();
        $stillSizes = $c->getStillSizes();

        return [
            'url' => $c->getSecureBaseUrl(),
            'backdrop_sizes' => $backdropSizes,
            'logo_sizes' => $logoSizes,
            'poster_sizes' => $posterSizes,
            'profile_sizes' => $profileSizes,
            'still_sizes' => $stillSizes
        ];
    }
}

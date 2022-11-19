<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserMovieRepository;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
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
        return $this->redirectToRoute('app_home', ['_locale' => $locale]);
    }

    #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'fr|en|de|es', 'page'=>1, 'sort_by'=>'popularity.desc'])]
    public function index(Request $request/*, TMDBService $callTmdbService, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration*/): Response
    {
        /** @var User $user */
//        $user = $this->getUser();
        return $this->render('home/index.html.twig', [
            'from' => 'home',
        ]);
    }
}

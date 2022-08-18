<?php

namespace App\Controller;

use App\Entity\MyMovieCollection;
use App\Entity\User;
use App\Repository\MyMovieCollectionRepository;
use App\Repository\UserMovieRepository;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CollectionController extends AbstractController
{
    #[Route('/{_locale}/collections', name: 'app_collection', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(MyMovieCollectionRepository $collectionRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $collections = $collectionRepository->findBy(['user' => $user]);
//        dump($collections);

        return $this->render('collection/index.html.twig', [
            'collections' => $collections,
        ]);
    }

    #[Route('/{_locale}/collections/{id}', name: 'app_collection_display', requirements: ['_locale' => 'fr|en|de|es'])]
    public function display(Request $request, MyMovieCollection $movieCollection, MovieController $movieController, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

//        $movies = $movieCollection->getMovies();
//        foreach ($movies as $movie) {
//            dump($movie);
//        }
        $imageConfig = $imageConfiguration->getConfig();

        return $this->render('collection/display.html.twig', [
            'collection' => $movieCollection,
            'userMovies' => $movieController->getUserMovieIds($userMovieRepository),
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
        ]);
    }
}

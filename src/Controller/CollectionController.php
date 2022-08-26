<?php

namespace App\Controller;

use App\Entity\MovieCollection;
use App\Entity\User;
use App\Repository\MovieCollectionRepository;
use App\Repository\UserMovieRepository;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CollectionController extends AbstractController
{
    #[Route('/{_locale}/collections', name: 'app_collection', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(MovieCollectionRepository $collectionRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $collections = $collectionRepository->findBy(['user' => $user]);

        return $this->render('collection/index.html.twig', [
            'collections' => $collections,
            'user' => $user,
        ]);
    }

    #[Route('/{_locale}/collections/{id}', name: 'app_collection_display', requirements: ['_locale' => 'fr|en|de|es'])]
    public function display(Request $request, MovieCollection $movieCollection, MovieController $movieController, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $imageConfig = $imageConfiguration->getConfig();

        return $this->render('collection/show.html.twig', [
            'collection' => $movieCollection,
            'userMovies' => $movieController->getUserMovieIds($userMovieRepository),
            'user' => $user,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\MovieList;
use App\Entity\User;
use App\Form\MovieCollectionType;
use App\Repository\MovieListRepository;
use App\Repository\MovieRepository;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/movie/list', requirements: ['_locale' => 'fr|en|de|es'])]
class MovieListController extends AbstractController
{
    public function __construct(
        private readonly ImageConfiguration  $imageConfiguration,
        private readonly MovieController     $movieController,
        private readonly MovieListRepository $movieListRepository,
        private readonly FileUploader        $fileUploader,
    )
    {
    }

    #[Route('/', name: 'app_movie_list_index')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $movieLists = $this->movieListRepository->findBy(['user' => $user], ['title' => 'ASC']);

        return $this->render('movie_lists/index.html.twig', [
            'movieLists' => $movieLists,
            'from' => $request->query->get('from') ?? 'app_movie_list_index',
            'user' => $user,
        ]);
    }

    #[Route('/show/{id}', name: 'app_movie_list_show', methods: ['GET'])]
    public function show(Request $request, MovieList $movieList): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $before = $request->query->get('from');

        $imageConfig = $this->imageConfiguration->getConfig();

        // Trier les films sur la date de sortie (releaseDate)
        $movies = array_map(function ($movie) {
            return $movie->toArray();
        }, $movieList->getMovies()->toArray());
        uksort($movies, function ($a, $b) use ($movies) {
            return $movies[$b]['releaseDate'] <=> $movies[$a]['releaseDate'];
        });

        return $this->render('movie_lists/show.html.twig', [
            'movieList' => $movieList,
            'movies' => $movies,
            'userMovies' => $this->movieController->getUserMovieIds(),
            'user' => $user,
            'before' => $before ?? 'app_movie_list_index',
            'from' => 'app_movie_list_show',
            'mandatory' => ['id' => $movieList->getId()],
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/toggle', name: 'app_movie_list_toggle')]
    public function toggleMovieToCollection(Request $request): Response
    {
        $collectionId = $request->query->getInt("c");
        $action = $request->query->get("a");
        $movieId = $request->query->getInt("m");

        $collection = $this->movieCollectionRepository->find($collectionId);
        $movie = $this->movieRepository->findOneBy(["movieDbId" => $movieId]);

        if ($action == "a") $collection->addMovie($movie);
        if ($action == "r") $collection->removeMovie($movie);
        $this->movieCollectionRepository->add($collection, true);

        $message = "The movie « movie_name » has been " . ($action == "a" ? "added to" : "removed from") . " your collection « collection_name ».";
        $message = $this->translator->trans($message, ["movie_name" => $movie->getTitle(), "collection_name" => $collection->getTitle()], "messages");
        return $this->json(["message" => $message]);
    }

    #[Route('/new', name: 'app_movie_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $movieList = new MovieList($user);
        $form = $this->createForm(MovieCollectionType::class, $movieList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $movieList);
            return $this->redirectToRoute('$app_movie_list');
        }

        return $this->render('movie_lists/new.html.twig', [
            'form' => $form->createView(),
            'movieList' => $movieList,
            'user' => $user,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_movie_list_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MovieList $movieList): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(MovieCollectionType::class, $movieList);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $movieList);
            return $this->redirectToRoute('$app_movie_list');
        }

        return $this->render('movie_lists/edit.html.twig', [
            'form' => $form->createView(),
            'title' => $movieList->getTitle(),
            'user' => $user,
        ]);
    }

    function handleForm($form, MovieList $movieList): void
    {
        /** @var UploadedFile $avatarFile */
        $thumbnailFile = $form->get('dropThumbnail')->getData();
        if ($thumbnailFile) {
            $thumbnailFileName = $this->fileUploader->upload($thumbnailFile, 'movie_lists_thumbnail');
            $fileToBeRemoved = $movieList->getThumbnail();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'movie_lists_thumbnail');
            }
            $movieList->setThumbnail($thumbnailFileName);
        }
        /** @var UploadedFile $bannerFile */
        $bannerFile = $form->get('dropBanner')->getData();
        if ($bannerFile) {
            $bannerFileName = $this->fileUploader->upload($bannerFile, 'movie_lists_banner');
            $fileToBeRemoved = $movieList->getBanner();
            if ($fileToBeRemoved) {
                $this->fileUploader->removeFile($fileToBeRemoved, 'movie_lists_banner');
            }
            $movieList->setBanner($bannerFileName);
        }
        $this->movieListRepository->add($movieList, true);
    }

    #[Route('/delete/{id}', name: 'app_movie_list_delete', methods: ['GET'])]
    public function delete(MovieList $movieList): JsonResponse
    {
        if ($movieList->getThumbnail()) {
            $this->fileUploader->removeFile($movieList->getThumbnail(), 'movie_lists_thumbnail');
        }
        if ($movieList->getBanner()) {
            $this->fileUploader->removeFile($movieList->getBanner(), 'movie_lists_banner');
        }
        $this->movieListRepository->remove($movieList, true);

        return $this->json(['status' => 200]);
    }
}

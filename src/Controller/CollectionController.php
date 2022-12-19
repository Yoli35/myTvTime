<?php

namespace App\Controller;

use App\Entity\MovieCollection;
use App\Entity\User;
use App\Form\MovieCollectionType;
use App\Repository\MovieCollectionRepository;
use App\Repository\UserMovieRepository;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/collection', requirements: ['_locale' => 'fr|en|de|es'])]
class CollectionController extends AbstractController
{
    public function __construct(private readonly LogService $logService)
    {
    }

    #[Route('/', name: 'app_collection')]
    public function index(Request $request, MovieCollectionRepository $collectionRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $collections = $collectionRepository->findBy(['user' => $user]);

        return $this->render('collection/index.html.twig', [
            'collections' => $collections,
            'user' => $user,
        ]);
    }

    #[Route('/show/{id}', name: 'app_collection_show', methods: ['GET'])]
    public function show(Request $request, MovieCollection $movieCollection, MovieController $movieController, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
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

    #[Route('/new', name: 'app_collection_new',methods: ['GET', 'POST'])]
    public function new(Request $request, MovieCollectionRepository $repository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $collection = new MovieCollection($user);
        $form = $this->createForm(MovieCollectionType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $collection, $fileUploader, $repository);
            return $this->redirectToRoute('app_collection');
        }

        return $this->render('collection/new.html.twig', [
            'form' => $form->createView(),
            'collection' => $collection,
            'user' => $user,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MovieCollection $collection, MovieCollectionRepository $repository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(MovieCollectionType::class, $collection);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($form, $collection, $fileUploader, $repository);
            return $this->redirectToRoute('app_collection');
        }

        return $this->render('collection/edit.html.twig', [
            'form' => $form->createView(),
            'title' => $collection->getTitle(),
            'user' => $user,
        ]);
    }

    function handleForm($form, MovieCollection $collection, FileUploader $fileUploader, MovieCollectionRepository $repository): void
    {
        /** @var UploadedFile $avatarFile */
        $thumbnailFile = $form->get('dropThumbnail')->getData();
        if ($thumbnailFile) {
            $thumbnailFileName = $fileUploader->upload($thumbnailFile, 'collection_thumbnail');
            $fileToBeRemoved = $collection->getThumbnail();
            if ($fileToBeRemoved) {
                $fileUploader->removeFile($fileToBeRemoved, 'collection_thumbnail');
            }
            $collection->setThumbnail($thumbnailFileName);
        }
        /** @var UploadedFile $bannerFile */
        $bannerFile = $form->get('dropBanner')->getData();
        if ($bannerFile) {
            $bannerFileName = $fileUploader->upload($bannerFile, 'collection_banner');
            $fileToBeRemoved = $collection->getBanner();
            if ($fileToBeRemoved) {
                $fileUploader->removeFile($fileToBeRemoved, 'collection_banner');
            }
            $collection->setBanner($bannerFileName);
        }
        $repository->add($collection, true);
    }

    #[Route('/delete/{id}', name: 'app_collection_delete', methods: ['GET'])]
    public function delete(MovieCollection $collection, MovieCollectionRepository $repository, FileUploader $fileUploader): JsonResponse
    {
        if ($collection->getThumbnail()) {
            $fileUploader->removeFile($collection->getThumbnail(), 'collection_thumbnail');
        }
        if ($collection->getBanner()) {
            $fileUploader->removeFile($collection->getBanner(), 'collection_banner');
        }
        $repository->remove($collection, true);

        return $this->json(['status' => 200]);
    }
}

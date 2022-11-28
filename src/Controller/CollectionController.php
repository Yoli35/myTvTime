<?php

namespace App\Controller;

use App\Entity\MovieCollection;
use App\Entity\User;
use App\Form\MovieCollectionType;
use App\Repository\MovieCollectionRepository;
use App\Repository\UserMovieRepository;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    #[Route('/{_locale}/collection/new', name: 'app_collection_new', requirements: ['_locale' => 'fr|en|de|es'])]
    public function new(Request $request, MovieCollectionRepository $repository, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
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
}

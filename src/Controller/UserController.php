<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserMovieRepository;
use App\Service\BetaSeriesService;
use App\Service\FileUploader;
use App\Service\ImageConfiguration;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{_locale}/user/profile', name: 'app_user_profile', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, BetaSeriesService $betaSeriesService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $avatarFileName = $fileUploader->upload($avatarFile, 'avatar');
                $fileToBeRemoved = $user->getAvatar();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'avatar');
                }
                $user->setAvatar($avatarFileName);
            }
            /** @var UploadedFile $bannerFile */
            $bannerFile = $form->get('banner')->getData();
            if ($bannerFile) {
                $bannerFileName = $fileUploader->upload($bannerFile, 'banner');
                $fileToBeRemoved = $user->getBanner();
                if ($fileToBeRemoved) {
                    $fileUploader->removeFile($fileToBeRemoved, 'banner');
                }
                $user->setBanner($bannerFileName);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_profile');
        }

        $banner = [];
        if ($user->getBanner() == null) {
            $banner = $this->setRandomBanner($betaSeriesService);
        }
        return $this->render('user_account/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'banner' => $banner,
        ]);
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/user/movies', name: 'app_user_movies', requirements: ['_locale' => 'fr|en|de|es'])]
    public function userMovies(Request $request, UserMovieRepository $userMovieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** TODO  Export Movie List as a json file */
        /** TODO  Progressive Load of Movie List */

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $movies = $userMovieRepository->findUserMovies($user->getId());
        $imageConfig = $imageConfiguration->getConfig();

        $total = 0;
        foreach ($movies as $movie) {
            $total += $movie['runtime'];
        }
        $runtime['total'] = $total;
        $runtime['minutes'] = $total % 60;
        $runtime['hours'] = floor($total/60) % 24;
        $runtime['days'] = floor($total/60/24) % 30.41666667;
        $runtime['months'] = floor($total/60/24/30.41666667) % 12;
        $runtime['years'] = floor($total/60/24/365);

        return $this->render('user_account/user_movies.html.twig', [
            'discovers' => $movies,
            'runtime' => $runtime,
            'imageConfig' => $imageConfig,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function setRandomBanner(BetaSeriesService $betaSeriesService): array
    {
        $standing = $betaSeriesService->showsList(rand(1, 10));
        $discovers = json_decode($standing, true, 512, 0);
        $discover = $discovers['shows'][rand(0, 19)];
        $banner['image'] = $discover['images']['show'] ? : $discover['images']['banner'];
        $banner['title'] = $discover['title'];
        return $banner;
    }
}

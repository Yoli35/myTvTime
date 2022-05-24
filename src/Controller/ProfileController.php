<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\BetaSeriesService;
use App\Service\FileUploader;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Config\Doctrine\Orm\EntityManagerConfig;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProfileController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{_locale}/user/profile', name: 'app_user_profile', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader, WeatherService $weatherService, BetaSeriesService $betaSeriesService): Response
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

        $weather = [];
        $astro = [];
        if ($user->getCity()) {
            $locale = $request->getLocale();
            $standing = $weatherService->getLocalWeather($user->getCity(), $locale);
            $weather = json_decode($standing, true, 512, 0);
            $standing = $weatherService->getLocalAstronomy($user->getCity(), date("Y-m-d"), $locale);
            $astro = json_decode($standing, true, 512, 0);
            $standing = $weatherService->getLocalForecast($user->getCity(), date("Y-m-d"), 4, $locale);
            $forecast = json_decode($standing, true, 512, 0);
            dump($forecast);
        }
        else {
            $astro['astronomy']['astro'] = [];
        }
        $banner = [];
        if ($user->getBanner() == null) {
            $banner = $this->setRandomBanner($betaSeriesService);
        }
        return $this->render('user_account/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'banner' => $banner,
            'weather' => $weather,
            'astro' => $astro['astronomy']['astro']
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

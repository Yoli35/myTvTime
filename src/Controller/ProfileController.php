<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\BetaSeriesService;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    #[Route('/{_locale}/profile', name: 'app_profile', requirements: ['_locale' => 'fr|en|de|es'])]
    public function index(Request $request, BetaSeriesService $betaSeriesService): Response
    {
        $user = $this->getUser();
        $banner = [];

        if ($user->getBanner() == null) {
            $banner = $this->setRandomBanner($betaSeriesService);
        }
        return $this->render('user_account/index.html.twig', [
            'user' => $user,
            'banner' => $banner,
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

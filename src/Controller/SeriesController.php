<?php

namespace App\Controller;

use App\Service\BetaSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SeriesController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/series/{page}', name: 'app_home_series', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function index($page, BetaSeriesService $betaSeriesService): Response
    {
        $standing = $betaSeriesService->showsList($page);
        $discovers = json_decode($standing, true, 512, 0);

        return $this->render('home/index.html.twig', [
            'discovers' => $discovers,
            'page' => $page,
            'dRoute' => 'app_show',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/show/{id}', name: 'app_show', requirements: ['_locale' => 'fr|en|de|es'])]
    public function show($id, BetaSeriesService $betaSeriesService): Response
    {
        $standing = $betaSeriesService->showsDisplay($id);
        $result = json_decode($standing, true, 512, 0);

        return $this->render('series/index.html.twig', [
            'show' => $result['show'],
        ]);
    }
}

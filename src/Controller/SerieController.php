<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Repository\SerieRepository;
use App\Service\CallImdbService;
use App\Service\CallTmdbService;
use App\Service\ImageConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/{_locale}/serie', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieController extends AbstractController
{
    #[Route('/', name: 'app_serie_index', requirements: ['page' => 1], methods: ['GET'])]
    public function index(Request $request, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $orderBy = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');

        $totalResults = $serieRepository->count([]);
        $results = $serieRepository->findAllSeries($page, $perPage, $orderBy, $order);

        return $this->render('serie/index.html.twig', [
            'series' => $results,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'paginator' => $this->paginator($totalResults, $page, $perPage, 7),
                'per_page_values' => [1 => 10, 2 => 20, 3 => 50, 4 => 100],
                'order_by' => $orderBy,
                'order' => $order],
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    public function paginator($totalResults, $page = 1, $perPage = 20, $linkCount = 7): array
    {
        $first = 1;
        $totalPages = ceil($totalResults / $perPage); // ceil(88 / 10) -> 9

        if ($linkCount > $totalPages) {
            $linkCount = $totalPages;
        }

        $center = ceil($linkCount / 2); // ceil(7 / 2) -> 4
        $first = $page > $center ? $page - ($center - 1) : 1; // 1
        $first = (($first + $linkCount) > $totalPages) ? $totalPages - $linkCount + 1 : $first;
        $last = $first + $linkCount - 1;

        return [
            'total_pages' => $totalPages,
            'link_count' => $linkCount,
            'first_link' => $first,
            'last_link' => $last,
        ];
    }

    #[Route('/new', name: 'app_serie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SerieRepository $serieRepository): Response
    {
        $serie = new Serie();
        $form = $this->createForm(SerieType::class, $serie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serieRepository->add($serie, true);

            return $this->redirectToRoute('app_serie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('serie/new.html.twig', [
            'serie' => $serie,
            'form' => $form,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{id}', name: 'app_serie_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie, CallTmdbService $tmdbService): Response
    {
        $tv = $tmdbService->getTv($serie->getSerieId(), $request->getLocale());
        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_serie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Serie $serie, SerieRepository $serieRepository): Response
    {
        $form = $this->createForm(SerieType::class, $serie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serieRepository->add($serie, true);

            return $this->redirectToRoute('app_serie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('serie/edit.html.twig', [
            'serie' => $serie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_serie_delete', methods: ['POST'])]
    public function delete(Request $request, Serie $serie, SerieRepository $serieRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $serie->getId(), $request->request->get('_token'))) {
            $serieRepository->remove($serie, true);
        }

        return $this->redirectToRoute('app_serie_index', [], Response::HTTP_SEE_OTHER);
    }
}

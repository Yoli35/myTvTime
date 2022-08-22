<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Entity\User;
use App\Form\SerieType;
use App\Repository\SerieRepository;
use App\Service\CallTmdbService;
use App\Service\ImageConfiguration;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use DateTimeImmutable;

#[Route('/{_locale}/serie', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieController extends AbstractController
{
    /*
     * Pagination : nombre de liens de page
     */
    const LINK_COUNT = 7;
    const PER_PAGE_ARRAY = [1 => 10, 2 => 20, 3 => 50, 4 => 100];

    #[Route('/', name: 'app_serie_index', requirements: ['page' => 1], methods: ['GET'])]
    public function index(Request $request, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $settingsChanged = $request->query->getInt('s');
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $orderBy = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');

        if ($settingsChanged) {
            setcookie("series", json_encode(['pp' => $perPage, 'ob' => $orderBy, 'o' => $order]), strtotime('+30 days'), '/');
        }
        if ($request->query->count() == 0) {
            if (isset($_COOKIE['series'])) {
                $cookie = json_decode($_COOKIE['series'], true);
                $perPage = $cookie['pp'];
                $orderBy = $cookie['ob'];
                $order = $cookie['o'];
            }
        }
        $totalResults = $serieRepository->count([]);
        $results = $serieRepository->findAllSeries($page, $perPage, $orderBy, $order);

        return $this->render('serie/index.html.twig', [
            'series' => $results,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY,
                'order_by' => $orderBy,
                'order' => $order],
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    public function paginator($totalResults, $page = 1, $perPage = 20, $linkCount = 7): array
    {
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

    #[Route('/new', name: 'app_serie_new', methods: ['GET'])]
    public function new(Request $request, CallTmdbService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $value = $request->query->get("value");
        $page = $request->query->get("p");
        $perPage = $request->query->get("pp");
        $orderBy = $request->query->get("ob");
        $order = $request->query->get("o");
        $tv = ['name' => ''];
        $serieId = "";
        $status = "Ko";
        $response = "Not found";
        $card = "";
        $pagination = "";

        if (is_numeric($value)) {
            $serieId = $value;
        } else {
            if (preg_match("~(\d+)~", $value, $matches) == 1) {
                $serieId = $matches[0];
            }
        }
        if (strlen($serieId)) {
            $standing = $tmdbService->getTv($serieId, 'fr');

            if (strlen($standing)) {
                $status = "Ok";
                $tv = json_decode($standing, true);

                if (is_array($tv['networks']) && count($tv['networks'])) {
                    $network = $tv['networks'][0];
                } else {
                    $network = null;
                }
                $serie = $serieRepository->findOneBy(['serieId' => $serieId]);

                if ($serie == null) {
                    $serie = new Serie();
                    $response = "New";
                } else {
                    $response = "Update";
                }

                $serie->setName($tv['name']);
                $serie->setPosterPath($tv['poster_path']);
                $serie->setBackdropPath($tv['backdrop_path']);
                $serie->setOverview($tv['overview']);
                $serie->setSerieId($tv['id']);
                $serie->setFirstDateAir(new DateTimeImmutable($tv['first_air_date'] . 'T00:00:00'));
                if ($network) {
                    $serie->setNetwork($network['name']);
                    $serie->setNetworkLogoPath($network['logo_path']);
                }
                $serie->addUser($user);
                $serieRepository->add($serie, true);

                $card = $this->render('blocks/serie/card.html.twig', ['serie' => $serie, 'imageConfig' => $imageConfiguration->getConfig()]);

                $totalResults = $serieRepository->count([]);
                $pagination = $this->render('blocks/serie/pagination.html.twig', [
                    'pages' => [
                        'total_results' => $totalResults,
                        'page' => $page,
                        'per_page' => $perPage,
                        'link_count' => self::LINK_COUNT,
                        'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                        'per_page_values' => self::PER_PAGE_ARRAY,
                        'order_by' => $orderBy,
                        'order' => $order],
                ]);
            }
        }

        return $this->json([
            'serie' => $tv['name'],
            'status' => $status,
            'response' => $response,
            'id' => $serieId ?: $value,
            'card' => $card,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}', name: 'app_serie_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie, CallTmdbService $tmdbService, ImageConfiguration $imageConfiguration): Response
    {
        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);

        $standing = $tmdbService->getTvCredits($serie->getSerieId(), $request->getLocale());
        $credits = json_decode($standing, true);
        dump($credits);

        $standing = $tmdbService->getTvKeywords($serie->getSerieId(), $request->getLocale());
        $keywords = json_decode($standing, true);
        dump($keywords);

        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
            'credits' => $credits,
            'keywords' => $keywords,
            'locale' => $request->getLocale(),
            'imageConfig' => $imageConfiguration->getConfig(),
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

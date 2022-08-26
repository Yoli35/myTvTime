<?php

namespace App\Controller;

use App\Entity\Network;
use App\Entity\Serie;
use App\Entity\User;
use App\Form\SerieType;
use App\Repository\NetworkRepository;
use App\Repository\SerieRepository;
use App\Service\CallTmdbService;
use App\Service\ImageConfiguration;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTimeImmutable;

#[Route('/{_locale}/serie', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieController extends AbstractController
{
    /*
     * Pagination : nombre de liens de page
     */
    const LINK_COUNT = 7;
    const PER_PAGE_ARRAY = [1 => 10, 2 => 20, 3 => 50, 4 => 100];
    const MY_SERIES = 'my_series';
    const POPULAR = 'popular';
    const TO_RATED = 'top_rated';

    #[Route('/', name: 'app_serie_index', methods: ['GET'])]
    public function index(Request $request, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $settingsChanged = $request->query->getInt('s', 0);
        $backFromDetail = $request->query->getInt('b', 0);
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $orderBy = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');

        if ($settingsChanged) {
            setcookie("series", json_encode(['pp' => $perPage, 'ob' => $orderBy, 'o' => $order]), strtotime('+30 days'), '/');
        }
        if ($request->query->count() == 0 || $backFromDetail) {
            if (isset($_COOKIE['series'])) {
                $cookie = json_decode($_COOKIE['series'], true);
                $perPage = $cookie['pp'];
                $orderBy = $cookie['ob'];
                $order = $cookie['o'];
            }
            else {
                $perPage = 20;
                $orderBy = 'firstDateAir';
                $order = 'desc';
            }
        }
        $totalResults = $serieRepository->count([]);
        $results = $serieRepository->findAllSeries($user->getId(), $page, $perPage, $orderBy, $order);

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
            'user' => $user,
            'from' => self::MY_SERIES,
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/popular', name: 'app_serie_popular', methods: ['GET'])]
    public function popular(Request $request, CallTmdbService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();
        $standing = $tmdbService->getPopular($page, $locale);
        $popular = json_decode($standing, true);

        return $this->render('serie/popular.html.twig', $this->renderParams(self::POPULAR, $page, $popular, $serieRepository, $imageConfiguration));
    }

    #[Route('/top/rated', name: 'app_serie_top_rated', methods: ['GET'])]
    public function topRated(Request $request, CallTmdbService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();
        $standing = $tmdbService->getTopRated($page, $locale);
        $topRated = json_decode($standing, true);

        return $this->render('serie/popular.html.twig', $this->renderParams(self::TO_RATED, $page, $topRated, $serieRepository, $imageConfiguration));
    }

    public function renderParams($from, $page, $series, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): array
    {
        return [
            'series' => $series['results'],
            'mySerieIds' => $this->mySerieIds($serieRepository),
            'pages' => [
                'total_results' => $series['total_results'],
                'page' => $page,
                'per_page' => 20,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'from' => $from,
            'user' => $this->getUser(),
            'imageConfig' => $imageConfiguration->getConfig(),
        ];
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

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'app_serie_new', methods: ['GET'])]
    public function new(Request $request, CallTmdbService $tmdbService, SerieRepository $serieRepository, NetworkRepository $networkRepository, ImageConfiguration $imageConfiguration): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $from = $request->query->get('from', self::MY_SERIES);

        $value = $request->query->get("value");
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $orderBy = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');
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
            $standing = $tmdbService->getTv($serieId, $request->getLocale());

            if (strlen($standing)) {
                $status = "Ok";
                $tv = json_decode($standing, true);

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

                foreach ($tv['networks'] as $network) {
                    $m2mNetwork = $networkRepository->findOneBy(['name' => $network['name']]);

                    if ($m2mNetwork == null) {
                        $m2mNetwork = new Network();
                        $m2mNetwork->setName($network['name']);
                        $m2mNetwork->setLogoPath($network['logo_path']);
                        $m2mNetwork->setOriginCountry($network['origin_country']);
                        $networkRepository->add($m2mNetwork, true);
                    }
                    $serie->addNetwork($m2mNetwork);
                }
                $serie->addUser($user);
                $serieRepository->add($serie, true);

                $card = $this->render('blocks/serie/card.html.twig', [
                    'serie' => $serie,
                    'pages' => [
                        'page' => $page
                    ],
                    'from' => $from,
                    'imageConfig' => $imageConfiguration->getConfig()]);

                if ($from == self::MY_SERIES) {
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

//                $standing = $tmdbService->getTvKeywords($serieId, $request->getLocale());
//                $keywords = json_decode($standing, true);
//                $missingTranslation = $this->keywordsTranslation($keywords, $request->getLocale());
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

    public function keywordsTranslation($keywords, $locale): array
    {
        $translatedKeywords = $this->getTranslations($locale);
        $keywordsList = [];
        $keywordsOk = [];

        foreach ($keywords['results'] as $keyword) {
            $keywordsList[] = $keyword['name'];
            foreach ($translatedKeywords as $value) {
                if (!strcmp(trim($keyword['name']), trim($value[0]))) {
                    $keywordsOk[] = $keyword['name'];
                    break;
                }
            }
        }
        return array_diff($keywordsList, $keywordsOk);
    }

    public function getTranslations($locale): array
    {
        $filename = '../translations/tags.'.$locale.'.yaml';
        $res = fopen($filename, 'a+');
        $ks = [];

        while(!feof($res)) {
            $line = fgets($res);
            $ks[] = explode(": ", $line);
        }
        fclose($res);
        return $ks;
    }

    #[Route('/{id}', name: 'app_serie_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie, CallTmdbService $tmdbService, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);

        $standing = $tmdbService->getTvCredits($serie->getSerieId(), $request->getLocale());
        $credits = json_decode($standing, true);

        $standing = $tmdbService->getTvKeywords($serie->getSerieId(), $request->getLocale());
        $keywords = json_decode($standing, true);
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $standing = $tmdbService->getTvWatchProviders($serie->getSerieId());
        $temp = json_decode($standing, true);
        if (array_key_exists('FR', $temp['results'])) {
            $watchProviders = json_decode($standing, true)['results']['FR'];
        } else {
            $watchProviders = null;
        }

        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
            'credits' => $credits,
            'keywords' => $keywords,
            'missingTranslations' => $missingTranslations,
            'watchProviders' => $watchProviders,
            'locale' => $request->getLocale(),
            'page' => $page,
            'from' => $from,
            'user' => $this->getUser(),
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/tmdb/{id}', name: 'app_serie_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id, CallTmdbService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR);

        $standing = $tmdbService->getTv($id, $request->getLocale());
        $tv = json_decode($standing, true);

        $standing = $tmdbService->getTvCredits($id, $request->getLocale());
        $credits = json_decode($standing, true);

        $standing = $tmdbService->getTvKeywords($id, $request->getLocale());
        $keywords = json_decode($standing, true);
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $standing = $tmdbService->getTvWatchProviders($id);
        $temp = json_decode($standing, true);
        if (array_key_exists('FR', $temp['results'])) {
            $watchProviders = json_decode($standing, true)['results']['FR'];
        } else {
            $watchProviders = null;
        }

        $mySerieIds = $this->mySerieIds($serieRepository);
        $index = array_search($id, $mySerieIds['serieIds']);
        $index = $index ? $mySerieIds[$index] : false;

        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
            'index' => $index,
            'credits' => $credits,
            'keywords' => $keywords,
            'missingTranslations' => $missingTranslations,
            'watchProviders' => $watchProviders,
            'locale' => $request->getLocale(),
            'page' => $page,
            'from' => $from,
            'user' => $this->getUser(),
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    public function mySerieIds(SerieRepository $serieRepository): array
    {
        $mySerieIds = $serieRepository->findMySerieIds();
        $mySerieIds['ids'] = [];
        $mySerieIds['serieIds'] = [];
        foreach ($mySerieIds as $mySerieId) {
            if (array_key_exists('serieId', $mySerieId)) {
                $mySerieIds['serieIds'][] = $mySerieId['serieId'];
            }
        }
        return $mySerieIds;
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

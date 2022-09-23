<?php

namespace App\Controller;

use App\Entity\Network;
use App\Entity\Serie;
use App\Entity\SerieViewing;
use App\Entity\User;
use App\Form\SerieSearchType;
use App\Form\SerieType;
use App\Repository\NetworkRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const LATEST = 'latest';
    const SEARCH = 'search';
    const SERIE_PAGE = 'serie';

    #[Route('/', name: 'app_serie_index', methods: ['GET'])]
    public function index(Request $request, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration, QuoteService $quoteService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();

        $settingsChanged = $request->query->getInt('s');
        $backFromDetail = $request->query->getInt('b');
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
            } else {
                $perPage = 20;
                $orderBy = 'firstDateAir';
                $order = 'desc';
            }
        }
        $totalResults = $serieRepository->countUserSeries($user->getId());
        $results = $serieRepository->findAllSeries($user->getId(), $page, $perPage, $orderBy, $order);

        $list = $serieRepository->listUserSeries($user->getId());

        return $this->render('serie/index.html.twig', [
            'series' => $results,
            'numbers' => $serieRepository->numbers($user->getId())[0],
            'list' => $list,
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
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'from' => self::MY_SERIES,
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/search/{page}', name: 'app_serie_search', defaults: ['page' => 1], methods: ['GET', 'POST'])]
    public function search(Request $request, int $page, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $series = ['results' => [], 'page' => 0, 'total_pages' => 0, 'total_results' => 0];
        $query = $request->query->get('query');
        $year = $request->query->get('year');

        $form = $this->createForm(SerieSearchType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $form->getData();
            $query = $result['query'];
            $year = $result['year'];
            $page = 1;
        }
        if ($query && strlen($query)) {
            $standing = $tmdbService->search($page, $query, $year, $request->getLocale());
            dump($page, $query, $year);
            $series = json_decode($standing, true);
            dump('results', $series);
        }

        return $this->render('serie/search.html.twig', [
            'form' => $form->createView(),
            'query' => $query,
            'year' => $year,
            'series' => $series['results'],
            'serieIds' => $this->mySerieIds($serieRepository, $this->getUser()),
            'pages' => [
                'page' => $page,
                'total_pages' => $series['total_pages'],
                'total_results' => $series['total_results'],
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'user' => $this->getUser(),
            'from' => self::SEARCH,
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/popular', name: 'app_serie_popular', methods: ['GET'])]
    public function popular(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        return $this->series(self::POPULAR, $request, $tmdbService, $serieRepository, $imageConfiguration);
    }

    #[Route('/top/rated', name: 'app_serie_top_rated', methods: ['GET'])]
    public function topRated(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        return $this->series(self::TOP_RATED, $request, $tmdbService, $serieRepository, $imageConfiguration);
    }

    #[Route('/airing/today', name: 'app_serie_airing_today', methods: ['GET'])]
    public function topAiringToday(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        return $this->series(self::AIRING_TODAY, $request, $tmdbService, $serieRepository, $imageConfiguration);
    }

    #[Route('/on/the/air', name: 'app_serie_on_the_air', methods: ['GET'])]
    public function topOnTheAir(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        return $this->series(self::ON_THE_AIR, $request, $tmdbService, $serieRepository, $imageConfiguration);
    }

    public function series($kind, Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();

        $standing = $tmdbService->getSeries($kind, $page, $locale);
        $series = json_decode($standing, true);
        dump($series);

        return $this->render('serie/popular.html.twig', $this->renderParams($kind, $page, $series, $serieRepository, $imageConfiguration));
    }

    public function renderParams($from, $page, $series, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): array
    {
        return [
            'series' => $series['results'],
            'serieIds' => $this->mySerieIds($serieRepository, $this->getUser()),
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
    public function new(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, NetworkRepository $networkRepository, ImageConfiguration $imageConfiguration): Response
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
                dump($tv);

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
                $serie->setNumberOfSeasons($tv['number_of_seasons']);
                $serie->setNumberOfEpisodes($tv['number_of_episodes']);
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
                dump($serie);

                if ($from != self::SERIE_PAGE && $from != self::SEARCH) {
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
        $filename = '../translations/tags.' . $locale . '.yaml';
        $res = fopen($filename, 'a+');
        $ks = [];

        while (!feof($res)) {
            $line = fgets($res);
            $ks[] = explode(": ", $line);
        }
        fclose($res);
        return $ks;
    }

    #[Route('/show/{id}', name: 'app_serie_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie, TMDBService $tmdbService, SerieRepository $serieRepository, SerieViewingRepository $viewingRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);
        dump($tv);

        return $this->getSerie($tv, $page, $from, $serie->getId(), $request, $tmdbService, $serieRepository, $serie, $viewingRepository, $imageConfiguration, $query, $year);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/tmdb/{id}', name: 'app_serie_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id, TMDBService $tmdbService, SerieRepository $serieRepository, SerieViewingRepository $viewingRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($id, $request->getLocale());
        $tv = json_decode($standing, true);

        return $this->getSerie($tv, $page, $from, $id, $request, $tmdbService, $serieRepository, null, $viewingRepository, $imageConfiguration, $query, $year);
    }

    #[Route('/tmdb/{id}/season/{seasonNumber}', name: 'app_serie_tmdb_season', methods: ['GET'])]
    public function season(Request $request, $id, $seasonNumber, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $from = $request->query->get('from');
        $page = $request->query->get('p');
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');

        $serie = $serieRepository->findOneBy(['serieId' => $id]);
        $standing = $tmdbService->getTvSeason($id, $seasonNumber, $request->getLocale());
        $season = json_decode($standing, true);

        return $this->render('serie/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'parameters' => [
                'from' => $from,
                'page' => $page,
                'query' => $query,
                'year' => $year,
                "backId" => $backId
            ],
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/tmdb/{id}/season/{seasonNumber}/episode/{episodeNumber}', name: 'app_serie_tmdb_episode', methods: ['GET'])]
    public function episode(Request $request, $id, $seasonNumber, $episodeNumber, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $from = $request->query->get('from');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');
        $page = $request->query->get('p');
        $query = $request->query->get('query');

        $serie = $serieRepository->findOneBy(['serieId' => $id]);
        $standing = $tmdbService->getTvEpisode($id, $seasonNumber, $episodeNumber, $request->getLocale());
        $episode = json_decode($standing, true);

        return $this->render('serie/episode.html.twig', [
            'serie' => $serie,
            'episode' => $episode,
            'parameters' => [
                'from' => $from,
                'page' => $page,
                'query' => $query,
                'year' => $year,
                "backId" => $backId
            ],
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/latest/serie', name: 'app_serie_latest', methods: ['GET'])]
    public function latest(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $standing = $tmdbService->getLatest($request->getLocale());
        $tv = json_decode($standing, true);
        return $this->getSerie($tv, 0, self::LATEST, 0, $request, $tmdbService, $serieRepository, null, null, $imageConfiguration);
    }

    public function getSerie($tv, $page, $from, $backId, $request, $tmdbService, $serieRepository, $serie, $viewingRepository, $imageConfiguration, $query = "", $year = ""): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $id = $tv['id'];
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
        $standing = $tmdbService->getTvSimilar($id);
        $similar = json_decode($standing, true);

        $index = false;
        $serieIds = [];
        if ($user) {
            // Est-ce une série ajoutée ? $index != null => Ok
            $serieIds = $this->mySerieIds($serieRepository, $user);
            foreach ($serieIds as $serieId) {
                if ($serieId == $id) {
                    $index = $id;
                    break;
                }
            }
        }

        $standing = $tmdbService->getTvImages($id, $request->getLocale());
        $images = json_decode($standing, true);

        $viewing = null;

        if ($index) {
            if ($serie == null) {
                $serie = $serieRepository->findOneBy(['serieId' => $id]);
            }
            if ($serie) {
                $viewing = $viewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
                if ($viewing == null) {
                    $viewing = new SerieViewing();
                    $viewing->setUser($user);
                    $viewing->setSerie($serie);
                    $viewing->setViewing($this->createViewingTab($tv));
                    $viewingRepository->add($viewing, true);
                }
            }
        }
        dump($viewing);

        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
            'index' => $index,
            'serieIds' => $serieIds,
            'credits' => $credits,
            'keywords' => $keywords,
            'missingTranslations' => $missingTranslations,
            'watchProviders' => $watchProviders,
            'similar' => $similar,
            'images' => $images,
            'locale' => $request->getLocale(),
            'page' => $page,
            'from' => $from,
            'backId' => $backId,
            'query' => $query,
            'year' => $year,
            'user' => $user,
            'viewing' => $viewing ? $viewing->getViewing() : null,
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);

    }

    public function createViewingTab($tv): array
    {
        $tab = [];
        /*
         * La saison 0 correspond aux épisodes spéciaux regroupés dans cette saison
         */
        if ($tv['seasons'][0]['season_number'] == 1) {
            $tab[] = [
                'season_number' => 0,
                'season_completed' => false,
                'air_date' => null,
                'episode_count' => 0,
                'episodes' => []
            ];
        }
        foreach ($tv['seasons'] as $season) {
            $ep = [];
            for ($i = 1; $i <= $season['episode_count']; $i++) {
                $ep[] = false;
            }
            $tab[] = [
                'season_number' => $season['season_number'],
                'season_completed' => false,
                'air_date' => $season['air_date'],
                'episode_count' => $season['episode_count'],
                'episodes' => $ep
            ];
        }
        return $tab;
    }

    #[Route(path: '/viewing', name: 'app_serie_viewing')]
    public function updateViewingTab(Request $request, SerieRepository $serieRepository, SerieViewingRepository $viewingRepository, TMDBService $tmdbService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $serieId = $request->query->getInt('id');
        $season = $request->query->getInt('s');
        $episode = $request->query->getInt('e');
        $newValue = $request->query->getInt('v');

        $episode = $newValue ? $episode : $episode - 1;

        $tv = json_decode($tmdbService->getTv($serieId, $request->getLocale()), true);
        $seasons = $tv['seasons'];
        $noSpecialEpisodes = $seasons[0]['season_number'] == 1 ? 1 : 0;
        dump($seasons, $noSpecialEpisodes);
        $serie = $serieRepository->findOneBy(['serieId' => $serieId]);
        $theViewing = $viewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]);
        $viewing = $theViewing->getViewing();

        $newTab = [];
        /*
         * Épisodes spéciaux : saison 0
         */
        if ($season == 0) {
            $newEpisodes = [];
            $episode_count = $viewing[0]['episode_count'];
            $air_date = array_key_exists('air_date', $viewing[0]) ? $viewing[0]['air_date'] : $seasons[0]['air_date'];
            for ($e = 0; $e < $episode_count; $e++) {
                $newEpisodes[] = $e + 1 <= $episode;
            }
            $full = !in_array(false, $newEpisodes, true);
            $newTab[] = [
                'season_number' => 0,
                'season_completed' => $full,
                'air_date' => $air_date,
                'episode_count' => $episode_count,
                'episodes' => $newEpisodes
            ];
            for ($i = 1; $i <= $serie->getNumberOfSeasons(); $i++) {
                $newTab[$i] = $viewing[$i];

            }
        } else {
            $newTab[0] = $viewing[0];
            if (!array_key_exists('air_date', $viewing[0])) $newTab[0]['air_date'] = $noSpecialEpisodes ? null : $seasons[0]['air_date'];
            /*
             * Les saisons précédentes
             */
            for ($s = 1; $s < $season; $s++) {
                $newEpisodes = [];
                $episode_count = $viewing[$s]['episode_count'];
                $air_date = array_key_exists('air_date', $viewing[$s]) ? $viewing[$s]['air_date'] : $seasons[$s-$noSpecialEpisodes]['air_date'];
                for ($e = 0; $e < $episode_count; $e++) {
                    $newEpisodes[] = true;
                }
                $newTab[] = [
                    'season_number' => $s,
                    'season_completed' => true,
                    'air_date' => $air_date,
                    'episode_count' => $episode_count,
                    'episodes' => $newEpisodes
                ];
            }
            /*
             * La saison ciblée
             */
            $newEpisodes = [];
            $episode_count = $viewing[$season]['episode_count'];
            $air_date = array_key_exists('air_date', $viewing[$season]) ? $viewing[$season]['air_date'] : $seasons[$season-$noSpecialEpisodes]['air_date'];
            dump($air_date);
            for ($e = 0; $e < $episode_count; $e++) {
                $newEpisodes[] = $e + 1 <= $episode;
            }
            $full = !in_array(false, $newEpisodes, true);
            $newTab[] = [
                'season_number' => $s,
                'season_completed' => $full,
                'air_date' => $air_date,
                'episode_count' => $episode_count,
                'episodes' => $newEpisodes
            ];
            dump($newTab);
            /*
             * Les saisons suivantes
             */
            $season_count = $serie->getNumberOfSeasons();
            for ($s = $season + 1; $s <= $season_count; $s++) {
                $newEpisodes = [];
                $episode_count = $viewing[$s]['episode_count'];
                $air_date = array_key_exists('air_date', $viewing[$s]) ? $viewing[$s]['air_date'] : $seasons[$s-$noSpecialEpisodes]['air_date'];
                for ($e = 0; $e < $episode_count; $e++) {
                    $newEpisodes[] = false;
                }
                $newTab[] = [
                    'season_number' => $s,
                    'season_completed' => false,
                    'air_date' => $air_date,
                    'episode_count' => $episode_count,
                    'episodes' => $newEpisodes
                ];
            }
        }
        $today = (new DateTime)->format("Y-m-d");
        dump($today);
        $seasons_completed = [];
        foreach ($newTab as $tab) {
            dump($tab);
            if ($tab['air_date'] <= $today)
                $seasons_completed[] = !in_array(false, $tab['episodes'], true);
        }
        $serie_completed = !in_array(false, $seasons_completed, true);

        $theViewing->setViewing($newTab);
        $viewingRepository->add($theViewing, true);

        $serie->setUpdatedAt(new DateTimeImmutable());
        $serie->setSerieCompleted($serie_completed);
        $serieRepository->add($serie, true);

        $blocks = [];
        foreach ($newTab as $tab) {
            if ($tab['episode_count']) {
                $blocks[] = [
                    'season' => $tab['season_number'],
                    'view' => $this->render('blocks/serie/viewing_season.html.twig', [
                        'viewing' => $newTab,
                        'season_number' => $tab['season_number'],
                        'episode_count' => $tab['episode_count'],
                        'season_completed' => $tab['season_completed'],
                    ])];
            }
        }

        return $this->json(['blocks' => $blocks]);
    }

    /**
     * @param SerieRepository $serieRepository
     * @param User|null $user
     * @return array [] Returns an array of ids of owned Series
     */
    public function mySerieIds(SerieRepository $serieRepository, User $user = null): array
    {
        if ($user) {
            $mySerieIds = $serieRepository->findMySerieIds($user->getId());
            $serieIds = [];
            foreach ($mySerieIds as $mySerieId) {
                $serieIds[] = $mySerieId['serieId'];
            }
            return $serieIds;
        }
        if ($this->getUser()) {
            dump('Check mySerieIds function parameters !!!');
        }
        return [];
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

    #[Route('/delete/{id}', name: 'app_serie_delete', methods: ['POST'])]
    public function delete(Request $request, Serie $serie, SerieRepository $serieRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $serie->getId(), $request->request->get('_token'))) {
            $serieRepository->remove($serie, true);
        }

        return $this->redirectToRoute('app_serie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/render/translation/fields', name: 'app_serie_render_translation_fields', methods: ['GET'])]
    public function renderTranslationFields(Request $request): Response
    {
        $keywords = json_decode($request->query->get('k'), true);

        return $this->render('blocks/serie/translationField.html.twig', [
            'keywords' => $keywords
        ]);
    }

    #[Route('/render/translation/select', name: 'app_serie_render_translation_select', methods: ['GET'])]
    public function renderTranslationSelect(Request $request): Response
    {
        return $this->render('blocks/serie/translationSelect.html.twig', [
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/render/translation/save', name: 'app_serie_render_translation_save', methods: ['GET'])]
    public function translationSave(Request $request): Response
    {
        $translations = json_decode($request->query->get('t'), true);
        $n = count($translations);

        $filename = '../translations/tags.' . $translations[0][1] . '.yaml';
        $res = fopen($filename, 'a+');

        for ($i = 1; $i < $n; $i++) {
            $line = $translations[$i][0] . ': ' . $translations[$i][1] . "\n";
            fputs($res, $line);
        }
        fclose($res);

        return $this->json(["result" => ($n - 1) . " ligne" . (($n - 1) > 1 ? "s" : "") . " ajoutée" . (($n - 1) > 1 ? "s" : "") . " au fichier « tags." . $translations[0][1] . ".yaml »."]);
    }
}

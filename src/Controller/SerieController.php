<?php

namespace App\Controller;

use App\Entity\Cast;
use App\Entity\EpisodeViewing;
use App\Entity\Favorite;
use App\Entity\Networks;
use App\Entity\SeasonViewing;
use App\Entity\Serie;
use App\Entity\SerieCast;
use App\Entity\SerieViewing;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\SerieSearchType;
use App\Repository\CastRepository;
use App\Repository\EpisodeViewingRepository;
use App\Repository\FavoriteRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieCastRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Service\LogService;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/serie', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieController extends AbstractController
{
    /*
     * Pagination : nombre de liens de page
     */
    const LINK_COUNT = 7;
    const PER_PAGE_ARRAY = [1 => 10, 2 => 20, 3 => 50, 4 => 100];
    const MY_SERIES = 'my_series';
    const MY_SERIES_TO_START = 'my_series_to_start';
    const MY_SERIES_TO_END = 'my_series_to_end';
    const POPULAR = 'popular';
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const SEARCH = 'search';

    public function __construct(private readonly CastRepository           $castRepository,
                                private readonly EpisodeViewingRepository $episodeViewingRepository,
                                private readonly FavoriteRepository       $favoriteRepository,
                                private readonly ImageConfiguration       $imageConfiguration,
                                private readonly LogService               $logService,
                                private readonly SeasonViewingRepository  $seasonViewingRepository,
                                private readonly SerieCastRepository      $serieCastRepository,
                                private readonly SerieRepository          $serieRepository,
                                private readonly SerieViewingRepository   $serieViewingRepository,
                                private readonly TMDBService              $TMDBService,
                                private readonly TranslatorInterface      $translator)
    {
    }

    #[Route('/', name: 'app_serie_index', methods: ['GET'])]
    public function index(Request $request, SettingsRepository $settingsRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        /** @var User $user */
        $user = $this->getUser();
        $serieRepository = $this->serieRepository;

        $settingsChanged = $request->query->getInt('s');
        $backFromDetail = $request->query->getInt('b');
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $sort = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');

        list($perPage, $sort, $order) = $this->cookies($request, $backFromDetail, $settingsChanged, $perPage, $sort, $order);

        if ($sort == 'modifiedAt') {
            $lastModifiedSerieViewings = $this->serieViewingRepository->findBy(['user' => $user], ['modifiedAt' => $order], $perPage, $perPage * ($page - 1));
            $results = array_map(function ($serieViewing) {
                return $serieViewing->getSerie();
            }, $lastModifiedSerieViewings);
        } else {
            $results = $serieRepository->findAllSeries($user->getId(), $page, $perPage, $sort, $order);
        }

        // Liste des séries ajoutées par l'utilisateur pour le menu de recherche
        $list = $serieRepository->listUserSeries($user->getId());
        $totalResults = count($list);

        $series = $totalResults ? $this->getSeriesViews($user, $results, $request->getLocale()) : null;

        $leafSettings = $settingsRepository->findOneBy(["user" => $user, "name" => "leaf"]);
        if ($leafSettings == null) {
            $leafSettings = new Settings();
            $leafSettings->setUser($user);
            $leafSettings->setName("leaf");
            $leafSettings->setData([
                ["data" => "30", "name" => "number", "type" => "range"],
                ["data" => ["30", "80"], "name" => "life-length", "type" => "interval"],
                ["data" => "180", "name" => "initial-angle", "type" => "range"],
                ["data" => "16", "name" => "turn-per-minute", "type" => "range"],
                ["data" => ["25", "200"], "name" => "scale", "type" => "interval"]
            ]);
            $settingsRepository->save($leafSettings, true);
        }

        $now = new DateTime();
        $now->setTime(0, 0);

        foreach ($series as &$serie) {
            $serie = $this->isSerieAiringSoon($serie, $now);
        }

        return $this->render('serie/index.html.twig', [
            'series' => $series,
            'numbers' => $serieRepository->numbers($user->getId())[0],
            'list' => $list,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY,
                'order_by' => $sort,
                'order' => $order],
            'user' => $user,
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'leafSettings' => $leafSettings,
            'from' => self::MY_SERIES,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    public function cookies($request, $backFromDetail, $somethingChanged, $perPage, $sort, $order): array
    {
        if ($somethingChanged) {
            setcookie("series", json_encode(['pp' => $perPage, 'ob' => $sort, 'o' => $order]), strtotime('+30 days'), '/');
        }
        if ($request->query->count() == 0 || $backFromDetail) {
            if (isset($_COOKIE['series'])) {
                $cookie = json_decode($_COOKIE['series'], true);
                $perPage = $cookie['pp'];
                $sort = $cookie['ob'];
                $order = $cookie['o'];
            } else {
                $perPage = 20;
                $sort = 'firstDateAir';
                $order = 'desc';
            }
        }

        return [$perPage, $sort, $order];
    }

    public function isSerieAiringSoon($serie, $now): array
    {
        $serie['today'] = false;
        $serie['tomorrow'] = false;
        if ($serie['viewing']->isSerieCompleted()) {
            return $serie;
        }
        $viewing = $serie['viewing'];
        $findIt = false;
        /** @var SerieViewing $viewing */
        foreach ($viewing->getSeasons() as $season) {
            if ($season->isSeasonCompleted() || $season->getSeasonNumber() == 0) {
                continue;
            }
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getViewedAt()) {
                    continue;
                }
                if ($episode->getAirDate()) {
                    $date = $episode->getAirDate();

                    if ($viewing->isTimeShifted()) {
                        $date = $date->modify('+1 day');
                    }
                    $episodeDiff = date_diff($now, $date);
                    if (!$findIt) {
                        if ($episodeDiff->days == 0) {
                            $serie['today'] = true;
                            $findIt = true;
                        }
                    }
                    if (!$findIt) {
                        if ($episodeDiff->days) {
                            if ($episodeDiff->invert) {
                                $serie['passed'] = $episode->getAirDate()->format("d/m/Y");
                                if ($episodeDiff->y) {
                                    $serie['passedText'] = $this->translator->trans("available since") . " " . $episodeDiff->y . " " . $this->translator->trans($episodeDiff->y > 1 ? "years" : "year");
                                } else {
                                    if ($episodeDiff->m) {
                                        $serie['passedText'] = $this->translator->trans("available since") . " " . $episodeDiff->m . " " . $this->translator->trans($episodeDiff->m > 1 ? "months" : "month");
                                    } else {
                                        if ($episodeDiff->d) {
                                            if ($episodeDiff->d == 1) {
                                                $serie['passedText'] = $this->translator->trans("available yesterday");
                                            } else {
                                                $serie['passedText'] = $this->translator->trans("available.since", ['%days%' => $episodeDiff->days]);
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($episodeDiff->days == 1) {
                                    $serie['tomorrow'] = true;
                                } else {
                                    $serie['next'] = $date->format("m/d/Y");
                                    $serie['nextText'] = $this->translator->trans("available.next", ['%days%' => $episodeDiff->days]);
                                    $serie['nextEpisodeDays'] = $episodeDiff->days;
                                }
                            }
                            $serie['nextEpisode'] = sprintf("S%02dE%02d", $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber());
                            $findIt = true;
                        }
                    }
                } else { // Nouvelle saison et nouvel épisode sans date de diffusion
                    $findIt = true;
                    $serie['nextEpisode'] = sprintf("S%02dE%02d", $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber());
                    $serie['nextEpisodeNoDate'] = true;
                }
                if ($findIt) {
                    break;
                }
            }
        }

        return $serie;
    }

    public function getSeriesViews($user, $results, $locale): array
    {
        $ids = array_map(function ($result) {
            return $result->getId();
        }, $results);
        $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $ids]);
        $favorites = $this->favoriteRepository->findBy(['type' => 'serie', 'userId' => $user->getId(), 'mediaId' => $ids]);
        $networks = $this->serieRepository->networks($ids);

        $series = [];
        /** @var Serie $result */
        foreach ($results as $result) {
            $serie = $this->serie2array($result, $locale);
            $serie['viewing'] = $this->getSerieViews($result, $serieViewings);
            $serie['favorite'] = $this->isFavorite($serie, $favorites);
            $serie['networks'] = $this->getNetworks($serie, $networks);

            $series[] = $serie;
        }
        return $series;
    }

    public function getSerieViews(Serie $serie, $serieViewings): ?SerieViewing
    {
        /** @var SerieViewing $serieViewing */
        foreach ($serieViewings as $serieViewing) {
            if ($serieViewing->getSerie()->getId() == $serie->getId()) {
                return $serieViewing;
            }
        }
        return null;
    }

    public function isFavorite($serie, $favorites): bool
    {
        /** @var Favorite $favorite */
        foreach ($favorites as $favorite) {
            if ($favorite->getMediaId() == $serie['id']) {
                return true;
            }
        }
        return false;
    }

    public function getNetworks($serie, $networks): array
    {
        $serieNetworks = [];
        /** @var Networks $network */
        foreach ($networks as $network) {
            if ($network['serieId'] == $serie['id']) {
                $serieNetworks[] = $network;
            }
        }
        return $serieNetworks;
    }

    public function serie2array(Serie $result, $locale): array
    {
        $tv = json_decode($this->TMDBService->getTv($result->getSerieId(), $locale), true);

        $serie['id'] = $result->getId();
        $serie['name'] = $result->getName();
        $serie['posterPath'] = $result->getPosterPath();
        $serie['backdropPath'] = $result->getBackdropPath();
        $serie['serieId'] = $result->getSerieId();
        $serie['firstDateAir'] = $result->getFirstDateAir();
        $serie['createdAt'] = $result->getCreatedAt();
        $serie['updatedAt'] = $result->getUpdatedAt();
        $serie['status'] = $result->getStatus();
        $serie['tmdb_status'] = $tv['status'];
        $serie['overview'] = $result->getOverview();
        $serie['networks'] = $result->getNetworks();
        $serie['numberOfEpisodes'] = $result->getNumberOfEpisodes();
        $serie['numberOfSeasons'] = $result->getNumberOfSeasons();
        $serie['originalName'] = $result->getOriginalName();

        $serie['tmdb_next_episode_to_air'] = $tv['next_episode_to_air'];

        return $serie;
    }

    #[Route('/today', name: 'app_serie_today', methods: ['GET'])]
    public function today(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        $day = $request->query->getInt('d');
        $week = $request->query->getInt('w');
        $month = $request->query->getInt('m');

        $datetime = $day . ' day ' . $week . ' week ' . $month . ' month';
        $date = new DateTimeImmutable($datetime);
        $date = $date->setTime(0, 0);
        $now = new DateTimeImmutable();
        $now = $now->setTime(0, 0);
        $diff = date_diff($now, $date);
        $delta = $diff->days;

        /** @var Serie[] $todayAirings */
        $todayAirings = $this->todayAiringSeries($date, $request->getLocale());
//        dump($todayAirings);
        $backdrop = $this->getTodayAiringBackdrop($todayAirings);

        return $this->render('serie/today.html.twig', [
            'todayAirings' => $todayAirings,
            'date' => $date,
            'backdrop' => $backdrop,
            'prev' => $delta * ($diff->invert ? -1 : 1),
            'next' => $delta * ($diff->invert ? -1 : 1),
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    public function todayAiringSeries(DateTimeImmutable $day, string $locale = 'fr'): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $theDayBefore = $day->sub(new DateInterval('P1D'));

        $episodeViewings = $this->episodeViewingRepository->findBy(['airDate' => $day]);
        $episodeViewings = array_merge($episodeViewings, $this->episodeViewingRepository->findBy(['airDate' => $theDayBefore]));

        $seasonArrays = [];
        foreach ($episodeViewings as $episodeViewing) {
            $seasonArrays[] = [
                'seasonViewing' => $episodeViewing->getSeason(),
                'episodeViewing' => $episodeViewing,
                'episode_number' => $episodeViewing->getEpisodeNumber(),
                'air_date' => $episodeViewing->getAirDate()
            ];
        }
        $serieArrays = [];
        foreach ($seasonArrays as $seasonArray) {
            /** @var SeasonViewing $season */
            $season = $seasonArray['seasonViewing'];
            $serieViewing = $season->getSerieViewing();
            if (!in_array($serieViewing, $serieArrays) && $serieViewing->getUser() === $user) {
                $serieArrays[] = [
                    'serieViewing' => $season->getSerieViewing(),
                    'seasonArray' => $seasonArray,
                    'season_number' => $season->getSeasonNumber()
                ];
            }
        }
        $theseDaysSeries = [];
        foreach ($serieArrays as $serieArray) {
            /** @var SerieViewing $s */
            $s = $serieArray['serieViewing'];
            $season = $serieArray['seasonArray'];
            $season_number = $serieArray['season_number'];
            $id = $s->getSerie()->getId();
            if (!key_exists($id, $theseDaysSeries)) {
                $theseDaysSeries[$id] = ['serie' => $s->getSerie(), 'serieArray' => $serieArray];
            }
            if (!key_exists('seasons', $theseDaysSeries[$id])) {
                $theseDaysSeries[$id]['seasons'] = [];
            }
            if (!key_exists($season_number, $theseDaysSeries[$id]['seasons'])) {
                $theseDaysSeries[$id]['seasons'][$season_number] = [];
            }
            if (!key_exists('episodes', $theseDaysSeries[$id]['seasons'][$season_number])) {
                $theseDaysSeries[$id]['seasons'][$season_number]['episodes'] = [];
            }
            $theseDaysSeries[$id]['seasons'][$season_number]['episodes'][] = $season['episode_number'];

            $standing = $this->TMDBService->getTv($s->getSerie()->getSerieId(), $locale);
            $theseDaysSeries[$id]['tmdbSerie'] = json_decode($standing, true);
//            dump($theseDaysSeries[$id]['tmdbSerie']);
            $networks = array_map(function ($network) {
                return [$network['id'] => $network['name']];
            }, $theseDaysSeries[$id]['tmdbSerie']['networks']);
            $theseDaysSeries[$id]['networks'] = $networks;
        }

        $todaySeries = [];
        foreach ($theseDaysSeries as $serie) {
            $viewing = $serie['serieArray']['serieViewing'];
            $isTimeShifted = $viewing->isTimeShifted();

            if ($isTimeShifted && !date_diff($serie['serieArray']['seasonArray']['air_date'], $theDayBefore)->days) {
                $todaySeries[] = $serie;
            }
            if (!$isTimeShifted && !date_diff($serie['serieArray']['seasonArray']['air_date'], $day)->days) {
                $todaySeries[] = $serie;
            }
        }

        return $todaySeries;
    }

    public function getTodayAiringBackdrop($airings): ?string
    {
        $index = rand(0, count($airings) - 1);
        $pos = 0;
        foreach ($airings as $airing) {
            if ($index === $pos++) {
                return $airing['serie']->getBackdropPath();
            }
        }
        return null;
    }

    #[Route('/to-start', name: 'app_serie_to_start', methods: ['GET'])]
    public function seriesToStart(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        $page = $request->query->getInt('p', 1);
        $perPage = 10;
        $sort = 'createdAt';
        $order = 'DESC';

        /** @var User $user */
        $user = $this->getUser();
        $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'viewedEpisodes' => 0], [$sort => $order], $perPage, ($page - 1) * $perPage);

        $locale = $request->getLocale();
        $seriesToBeStarted = $this->seriesToBeToArray($user, $serieViewings, $locale);

        $totalResults = $this->serieViewingRepository->count(['user' => $user, 'viewedEpisodes' => 0]);

//        $this->updateSerieViewingTable();

        return $this->render('serie/to_start.html.twig', [
            'series' => $seriesToBeStarted,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY,
                'order_by' => $sort,
                'order' => $order],
            'user' => $user,
            'from' => self::MY_SERIES_TO_START,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    function updateSerieViewingTable()
    {
//        $serieViewings = $this->serieViewingRepository->findAll();
        // adjust number of seasons
//        foreach ($serieViewings as $serieViewing) {
//            $serieViewing->setNumberOfSeasons($serieViewing->getSerie()->getNumberOfSeasons());
//            $this->serieViewingRepository->save($serieViewing);
//        }
        // adjust number of episodes
//        foreach ($serieViewings as $serieViewing) {
//            $numberOfEpisodes = 0;
//            foreach ($serieViewing->getSeasons() as $seasonViewing) {
//                if ($seasonViewing->getSeasonNumber() > 0) {
//                    $numberOfEpisodes += $seasonViewing->getEpisodeCount();
//                }
//            }
//            $serieViewing->setNumberOfEpisodes($numberOfEpisodes);
//            $this->serieViewingRepository->save($serieViewing);
//        }
        // adjust viewed episodes
//        foreach ($serieViewings as $serieViewing) {
//            $viewedEpisodes = 0;
//            foreach ($serieViewing->getSeasons() as $seasonViewing) {
//                if ($seasonViewing->getSeasonNumber() > 0) {
//                    foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
//                        if ($episodeViewing->getViewedAt()) {
//                            $viewedEpisodes++;
//                        }
//                    }
//                }
//            }
//            $serieViewing->setViewedEpisodes($viewedEpisodes);
//            $this->serieViewingRepository->save($serieViewing);
//        }
//        $this->serieViewingRepository->flush();

        // adjust serie_completed field
//        foreach ($serieViewings as $serieViewing) {
//            $serieViewing->setSerieCompleted($serieViewing->getNumberOfEpisodes() === $serieViewing->getViewedEpisodes());
//            $this->serieViewingRepository->save($serieViewing);
//        }
//        $this->serieViewingRepository->flush();
    }

    #[Route('/to-end', name: 'app_serie_to_end', methods: ['GET'])]
    public function seriesToEnd(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        $page = $request->query->getInt('p', 1);
        $perPage = 10;
        $sort = 'createdAt';
        $order = 'ASC';

        /** @var User $user */
        $user = $this->getUser();
        $serieViewings = $this->serieViewingRepository->getSeriesToEnd($user, $perPage, $page);

        $locale = $request->getLocale();
        $seriesToBeEnded = $this->seriesToBeToArray($user, $serieViewings, $locale);

        $totalResults = $this->serieViewingRepository->countUserSeriesToEnd($user);

        return $this->render('serie/to_end.html.twig', [
            'series' => $seriesToBeEnded,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY,
                'order_by' => $sort,
                'order' => $order],
            'user' => $user,
            'from' => self::MY_SERIES_TO_END,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    public function seriesToBeToArray($user, $serieViewings, $locale): array
    {
        $results = array_map(function ($serieViewing) {
            return $serieViewing->getSerie();
        }, $serieViewings);
        $ids = array_map(function ($result) {
            return $result->getId();
        }, $results);

        $favorites = $this->favoriteRepository->findBy(['type' => 'serie', 'userId' => $user->getId(), 'mediaId' => $ids]);
        $networks = $this->serieRepository->networks($ids);

        /** @var Serie $result */
        $seriesToBe = array_map(function ($result) use ($serieViewings, $locale, $favorites, $networks) {
            $serie = $this->serie2array($result, $locale);
            $serie['viewing'] = $this->getSerieViews($result, $serieViewings);
            $serie['favorite'] = $this->isFavorite($serie, $favorites);
            $serie['networks'] = $this->getNetworks($serie, $networks);
            return $serie;
        }, $results);

        $now = new DateTime();
        $now->setTime(0, 0);
        foreach ($seriesToBe as &$serie) {
            $serie = $this->isSerieAiringSoon($serie, $now);
        }

        return $seriesToBe;
    }

    #[Route('/search/{page}', name: 'app_serie_search', defaults: ['page' => 1], methods: ['GET', 'POST'])]
    public function search(Request $request, int $page): Response
    {
        $this->logService->log($request, $this->getUser());
        $tmdbService = $this->TMDBService;
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
            $series = json_decode($standing, true);
        }

        /** @var User $user */
        $user = $this->getUser();
        return $this->render('serie/search.html.twig', [
            'form' => $form->createView(),
            'query' => $query,
            'year' => $year,
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($user) : [],
            'pages' => [
                'page' => $page,
                'total_pages' => $series['total_pages'],
                'total_results' => $series['total_results'],
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'user' => $user,
            'from' => self::SEARCH,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/popular', name: 'app_serie_popular', methods: ['GET'])]
    public function popular(Request $request): Response
    {
        $this->logService->log($request, $this->getUser());
        return $this->series($request, self::POPULAR);
    }

    #[Route('/top/rated', name: 'app_serie_top_rated', methods: ['GET'])]
    public function topRated(Request $request): Response
    {
        $this->logService->log($request, $this->getUser());
        return $this->series($request, self::TOP_RATED);
    }

    #[Route('/airing/today', name: 'app_serie_airing_today', methods: ['GET'])]
    public function topAiringToday(Request $request): Response
    {
        $this->logService->log($request, $this->getUser());
        return $this->series($request, self::AIRING_TODAY);
    }

    #[Route('/on/the/air', name: 'app_serie_on_the_air', methods: ['GET'])]
    public function topOnTheAir(Request $request): Response
    {
        $this->logService->log($request, $this->getUser());
        return $this->series($request, self::ON_THE_AIR);
    }

    public function series(Request $request, $kind): Response
    {
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();

        $standing = $this->TMDBService->getSeries($kind, $page, $locale);
        $series = json_decode($standing, true);

        return $this->render('serie/popular.html.twig', $this->renderParams($kind, $page, $series));
    }

    public function renderParams($from, $page, $series): array
    {
        /** @var User $user */
        $user = $this->getUser();
        return [
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($user) : [],
            'pages' => [
                'total_results' => $series['total_results'],
                'page' => $page,
                'per_page' => 20,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'from' => $from,
            'user' => $user,
            'imageConfig' => $this->imageConfiguration->getConfig(),
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

    public function createSerieViewing($user, $tv, $serie): SerieViewing
    {
        $viewing = new SerieViewing();
        $viewing->setUser($user);
        $viewing->setSerie($serie);
        $viewing->setNumberOfSeasons($tv['number_of_seasons']);
        $viewing->setNumberOfEpisodes($tv['number_of_episodes']);
        $viewing->setSerieCompleted(false);
        $viewing->setTimeShifted(false);
        $viewing->setViewedEpisodes(0);
        $viewing = $this->createSerieViewingContent($viewing, $tv);
        $this->serieViewingRepository->save($viewing, true);

        return $viewing;
    }

    public function createSerieViewingContent(SerieViewing $viewing, array $tv): SerieViewing
    {
        foreach ($tv['seasons'] as $s) {
            if ($s['season_number']) { // 21/12/2022 : plus d'épisodes spéciaux
                $season = new SeasonViewing($s['air_date'], $s['season_number'], $s['episode_count'], false);
                $viewing->addSeason($season);
                $this->seasonViewingRepository->save($season, true);

                for ($i = 1; $i <= $season->getEpisodeCount(); $i++) {
                    $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $i, 'fr');
                    $tmdbEpisode = json_decode($standing, true);
                    $episode = new EpisodeViewing($i, $tmdbEpisode ? $tmdbEpisode['air_date'] : null);
                    $season->addEpisode($episode);
                    $this->episodeViewingRepository->save($episode, true);
                }
            }
        }
        return $viewing;
    }

    public function updateSerieViewing(SerieViewing $serieViewing, array $tv, ?Serie $serie): SerieViewing
    {
        $modified = false;
        if ($serieViewing->getNumberOfSeasons() != $tv['number_of_seasons']) {
            $serieViewing->setNumberOfSeasons($tv['number_of_seasons']);
            $modified = true;
        }
        if ($serieViewing->getNumberOfEpisodes() != $tv['number_of_episodes']) {
            $serieViewing->setNumberOfEpisodes($tv['number_of_episodes']);
            $modified = true;
        }
        if ($serieViewing->isSerieCompleted() == NULL) {
            $serieViewing->setSerieCompleted(false);
            $modified = true;
        }
        if ($serieViewing->getCreatedAt() == null) {
            try {
                $serieViewing->setCreatedAt(new DateTimeImmutable($tv['first_air_date']));
            } catch (\Exception $e) {
                $serieViewing->setCreatedAt(new DateTimeImmutable());
            }
            $modified = true;
        }
        if ($serieViewing->getModifiedAt() == null) {
            try {
                $serieViewing->setModifiedAt(new DateTime($tv['first_air_date']));
            } catch (\Exception $e) {
                $serieViewing->setModifiedAt(new DateTime());
            }
            $modified = true;
        }
        if ($modified) {
            $this->serieViewingRepository->save($serieViewing, true);
        }

        if ($serie !== null) {
            $modified = false;
            if ($serie->getNumberOfSeasons() != $tv['number_of_seasons']) {
                $serie->setNumberOfSeasons($tv['number_of_seasons']);
                $modified = true;
            }
            if ($serie->getNumberOfEpisodes() != $tv['number_of_episodes']) {
                $serie->setNumberOfEpisodes($tv['number_of_episodes']);
                $modified = true;
            }
            if ($modified) {
                $serie->setUpdatedAt(new DateTime());
                $this->serieRepository->save($serie, true);
            }
        }

        foreach ($tv['seasons'] as $s) {
            if ($s['season_number']) { // 21/12/2022 : plus d'épisodes spéciaux
                $season = $serieViewing->getSeasonByNumber($s['season_number']);
                if ($season === null) {
                    $season = new SeasonViewing($s['air_date'], $s['season_number'], $s['episode_count'], false);
                    $serieViewing->addSeason($season);
                    for ($i = 1; $i <= $s['episode_count']; $i++) {
                        $this->addNewEpisode($tv, $season, $i);
                    }
                    $this->seasonViewingRepository->save($season, true);
                } else {
                    if ($season->getEpisodeCount() < $s['episode_count']) {
                        for ($i = $season->getEpisodeCount() + 1; $i <= $s['episode_count']; $i++) {
                            $this->addNewEpisode($tv, $season, $i);
                        }
                        $season->setEpisodeCount($s['episode_count']);
                        $this->seasonViewingRepository->save($season, true);
                    } else {
                        foreach ($season->getEpisodes() as $episode) {
                            if ($episode->getAirDate() === null) {
                                $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $episode->getEpisodeNumber(), 'fr');
                                $tmdbEpisode = json_decode($standing, true);
                                if ($tmdbEpisode['air_date']) {
                                    try {
                                        $episode->setAirDate(new DateTimeImmutable($tmdbEpisode['air_date']));
                                        $this->episodeViewingRepository->save($episode, true);
                                        $this->addFlash('success', 'Date mise à jour : ' . $tmdbEpisode['air_date']);
                                    } catch (Exception $e) {
                                        $this->addFlash('danger', 'Erreur de date : ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->setViewedEpisodeCount($serieViewing);
        // Ajuste les champs seasonCount, seasonCompleted, serieCompleted
        $this->viewingCompleted($serieViewing);

        return $serieViewing;
    }

    public function addNewEpisode(array $tv, SeasonViewing $season, int $episodeNumber): void
    {
        $standing = $this->TMDBService->getTvEpisode($tv['id'], $season->getSeasonNumber(), $episodeNumber, 'fr');
        $tmdbEpisode = json_decode($standing, true);
        $episode = new EpisodeViewing($episodeNumber, $tmdbEpisode['air_date']);
        $episode->setSeason($season);
        $this->episodeViewingRepository->save($episode, true);
        $season->addEpisode($episode);
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
    public function show(Request $request, Serie $serie): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->logService->log($request, $this->getUser());

        $tmdbService = $this->TMDBService;

        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']);
        if ($standing == "") {
            return $this->render('serie/_error.html.twig', [
                'serie' => $serie,
            ]);
        }
        $tv = json_decode($standing, true);

        return $this->getSerie($request, $tv, $page, $from, $serie->getId(), $serie, $query, $year);
    }

    #[Route('/tmdb/{id}', name: 'app_serie_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id): Response
    {
        $this->logService->log($request, $this->getUser());
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $this->TMDBService->getTv($id, $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']);
        $tv = json_decode($standing, true);

        return $this->getSerie($request, $tv, $page, $from, $id, null, $query, $year);
    }

    #[Route('/tmdb/{id}/season/{seasonNumber}', name: 'app_serie_tmdb_season', methods: ['GET'])]
    public function season(Request $request, $id, $seasonNumber): Response
    {
        $this->logService->log($request, $this->getUser());

        $from = $request->query->get('from');
        $page = $request->query->get('p');
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');

        $serie = $this->serie($id, $request->getLocale());
        $standing = $this->TMDBService->getTvSeason($id, $seasonNumber, $request->getLocale(), ['credits']);
        $season = json_decode($standing, true);
        $credits = $season['credits'];
        if (!key_exists('cast', $credits)) {
            $credits['cast'] = [];
        }
        $episodes = [];
        foreach ($season['episodes'] as $episode) {
            $standing = $this->TMDBService->getTvEpisode($id, $seasonNumber, $episode['episode_number'], $request->getLocale(), ['credits']);
            $tmdbEpisode = json_decode($standing, true);
            if ($serie['userSerieViewing']) {
                if ($serie['userSerieViewing']->isTimeShifted()) {
                    if ($tmdbEpisode['air_date']) {
                        $dateString = $tmdbEpisode['air_date'] . 'T00:00:00';
                        try {
                            $airDate = new \DateTime($dateString);
                            $airDate->modify('+1 day');
                            $tmdbEpisode['air_date'] = $airDate->format('Y-m-d');
                        } catch (\Exception $e) {

                        }
                    }
                }
            }
            $episodes[] = $tmdbEpisode;
            if (key_exists('cast', $tmdbEpisode['credits'])) {
                foreach ($tmdbEpisode['credits']['cast'] as $cast) {
                    if (!in_array($cast, $credits['cast'])) {
                        $credits['cast'][] = $cast;
                    }
                }
            }
        }

        return $this->render('serie/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'episodes' => $episodes,
            'credits' => $credits,
            'parameters' => [
                'from' => $from,
                'page' => $page,
                'query' => $query,
                'year' => $year,
                "backId" => $backId
            ],
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    public function serie($id, $locale): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $serie = [];
        /** @var Serie $userSerie */
        $userSerie = $this->serieRepository->findOneBy(['serieId' => $id]);

        if ($userSerie == null) {
            $standing = $this->TMDBService->getTv($id, $locale);
            $tmdbSerie = json_decode($standing, true);
            $serie['id'] = $tmdbSerie['id'];
            $serie['name'] = $tmdbSerie['name'];
            $serie['backdropPath'] = $tmdbSerie['backdrop_path'];
            $serie['firstDateAir'] = $tmdbSerie['last_air_date'];
            $serie['posterPath'] = $tmdbSerie['poster_path'];
            $serie['userSerie'] = null;
            $serie['userSerieViewing'] = null;
        } else {
            $serie['id'] = $userSerie->getSerieId();
            $serie['name'] = $userSerie->getName();
            $serie['backdropPath'] = $userSerie->getBackdropPath();
            $serie['firstDateAir'] = $userSerie->getFirstDateAir();
            $serie['posterPath'] = $userSerie->getPosterPath();
            $serie['userSerie'] = $userSerie;
            if ($user != null) {
                $serie['userSerieViewing'] = $this->serieViewingRepository->findOneBy(['serie' => $userSerie, 'user' => $user]);
            } else {
                $serie['userSerieViewing'] = null;
            }
        }

        return $serie;
    }

    public function getSerie(Request $request, array $tv, int $page, string $from, $backId, Serie|null $serie, $query = "", $year = ""): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $serieRepository = $this->serieRepository;
        $imageConfiguration = $this->imageConfiguration;

        $id = $tv['id'];
        $credits = $tv['credits'];

        $keywords = $tv['keywords'];
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $temp = $tv['watch/providers'];
        if ($temp && array_key_exists('FR', $temp['results'])) {
            $watchProviders = $temp['results']['FR'];
        } else {
            $watchProviders = null;
        }

        $similar = $tv['similar'];

        $images = $tv['images'];

        $index = false;
        $serieIds = [];
        if ($user) {
            // Est-ce une série ajoutée ? $index != false => Ok
            $serieIds = $this->mySerieIds($user);
            if (in_array($id, $serieIds)) {
                $index = $id;
            }
        }

        $serieId = null;
        $whatsNew = null;
        $serieViewing = null;

        if ($index) {
            if ($serie === null) {
                $serie = $serieRepository->findOneBy(['serieId' => $id]);
            }
            if ($serie) {
                $serieId = $serie->getId();
                $whatsNew = $this->whatsNew($tv, $serie, $serieRepository);

                /** @var SerieViewing $serieViewing */
                $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
                if ($serieViewing == null) {
                    $serieViewing = $this->createSerieViewing($user, $tv, $serie);
                } else {
                    $serieViewing = $this->updateSerieViewing($serieViewing, $tv, $serie);
                }
                $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $tv['networks'], true, $locale);
                if (!count($serieViewing->getSerieCasts())) {
                    $this->updateTvCast($tv, $serieViewing);
                    $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
                }
                $cast = $this->getCast($serieViewing);
                $credits['cast'] = $cast;

                $seasonsWithAView = [];
                foreach ($tv['seasons'] as $season) {
                    $seasonWithAView = $season;
                    $seasonWithAView['seasonViewing'] = $serieViewing->getSeasonByNumber($season['season_number']);

                    if ($serieViewing->isTimeShifted()) {
                        try {
                            $airDate = new \DateTimeImmutable($season['air_date']);
                            $airDate = $airDate->modify('+1 day');
                            $seasonWithAView['air_date'] = $airDate->format('Y-m-d');
                        } catch (\Exception $e) {

                        }
                    }
                    $seasonsWithAView[] = $seasonWithAView;
                }
                $tv['seasons'] = $seasonsWithAView;
            }
        }
        $ygg = str_replace(' ', '+', $tv['name']);
        $yggOriginal = str_replace(' ', '+', $tv['original_name']);

//        $this->cleanCastTable();

        return $this->render('serie/show.html.twig', [
            'serie' => $tv,
            'serieId' => $serieId,
            'index' => $index,
            'serieIds' => $serieIds,
            'credits' => $credits,
            'keywords' => $keywords,
            'missingTranslations' => $missingTranslations,
            'watchProviders' => $watchProviders,
            'similar' => $similar,
            'images' => $images,
            'locale' => $locale,
            'page' => $page,
            'from' => $from,
            'backId' => $backId,
            'query' => $query,
            'year' => $year,
            'user' => $user,
            'whatsNew' => $whatsNew,
            'viewedEpisodes' => $serieViewing?->getViewedEpisodes(),
            'isTimeShifted' => $serieViewing?->isTimeShifted(),
            'nextEpisodeToWatch' => $nextEpisodeToWatch ?? null,
            'ygg' => $ygg,
            'yggOriginal' => $yggOriginal,
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);

    }

    public function cleanCastTable()
    {
        // Suppression des casts non-utilisés
        $castRepository = $this->castRepository;
        $serieCastRepository = $this->serieCastRepository;
        $serieCasts = $serieCastRepository->findAll();
        $casts = $castRepository->findAll();

        $serieCastCastIds = array_map(function ($serieCast) {
            return $serieCast->getCastId();
        }, $serieCasts);

        $castIds = array_map(function ($cast) {
            return $cast->getId();
        }, $casts);

        $castIds = array_diff($castIds, $serieCastCastIds);

        $casts = $castRepository->findBy(['id' => $castIds]);

        foreach ($casts as $cast) {
            $castRepository->remove($cast);
        }
        $castRepository->flush();
    }

    public function getNextEpisodeToWatch(SerieViewing $serieViewing, array $networks, bool $tvNetworks, $locale): ?array
    {
        $lastNotViewedEpisode = null;
        $seasons = $serieViewing->getSeasons();

        foreach ($seasons as $season) {
            if ($season->getSeasonNumber() && !$season->isSeasonCompleted()) {
                $episodes = $season->getEpisodes();
                foreach ($episodes as $episode) {
                    if (!$episode->getViewedAt()) {
                        $lastNotViewedEpisode = $episode;
                        break;
                    }
                }
            }
            if ($lastNotViewedEpisode) {
                break;
            }
        }

        if ($lastNotViewedEpisode) {
            $serieId = $serieViewing->getSerie()->getSerieId();
            $seasonNumber = $lastNotViewedEpisode->getSeason()->getSeasonNumber();
            $episodeNumber = $lastNotViewedEpisode->getEpisodeNumber();
            $standing = $this->TMDBService->getTvEpisode($serieId, $seasonNumber, $episodeNumber, $locale);
            $tmdbEpisode = json_decode($standing, true);

            $airDate = null;
            $interval = null;

            if ($tmdbEpisode['air_date'] == null) {
                return [
                    'episodeNumber' => $tmdbEpisode['episode_number'],
                    'seasonNumber' => $tmdbEpisode['season_number'],
                    'airDate' => $airDate,
                    'interval' => $interval,
                ];
            }
            try {
                $airDate = new \DateTimeImmutable($tmdbEpisode['air_date']);
            } catch (\Exception $e) {
                return [
                    'episodeNumber' => $tmdbEpisode['episode_number'],
                    'seasonNumber' => $tmdbEpisode['season_number'],
                    'airDate' => $airDate,
                    'interval' => $interval,
                ];
            }

            if ($serieViewing->isTimeShifted()) {
                $airDate = $airDate->modify('+1 day');
            }
            $now = new \DateTimeImmutable('now');
            $interval = date_diff($now, $airDate);

            if ($lastNotViewedEpisode->getAirDate() == null) {
                $lastNotViewedEpisode->setAirDate($airDate);
                $this->episodeViewingRepository->save($lastNotViewedEpisode, true);
            }

            return [
                'episodeNumber' => $tmdbEpisode['episode_number'],
                'seasonNumber' => $tmdbEpisode['season_number'],
                'airDate' => $airDate,
                'interval' => $interval,
            ];
        }

        return null;
    }

    public function getCast(SerieViewing $serieViewing): array
    {
        $ids = array_map(function ($serieCast) {
            return $serieCast->getCastId();
        }, $serieViewing->getSerieCasts()->toArray());

        $castDbArray = $this->castRepository->findBy(['id' => $ids]);
        $serieCastArray = $serieViewing->getSerieCasts()->toArray();

        /** @var SerieCast[] $serieCasts */
        $cast = array_map(function ($castDb) use ($serieCastArray) {
            $serieCast = array_filter($serieCastArray, function ($serieCast) use ($castDb) {
                return $serieCast->getCastId() == $castDb->getId();
            });
            $serieCast = array_values($serieCast)[0];
            /** @var SerieCast $serieCast */
            $c['id'] = $castDb->getTmdbId();
            $c['profile_path'] = $castDb->getProfilePath();
            $c['name'] = $castDb->getName();
            $c['known_for_department'] = $serieCast->getKnownForDepartment();
            $c['character'] = $serieCast->getCharacterName();
            $c['recurring_character'] = $serieCast->isRecurringCharacter();
            $c['guest_star'] = $serieCast->isGuestStar();
            $count = $serieCast->getEpisodesCount();
            $c['episodes'] = $count . ' ' . $this->translator->trans($count > 1 ? 'episodes' : 'episode');
            $c['episodesString'] = $serieCast->getEpisodesString();
            return $c;
        }, $castDbArray);

        // on replace les personnages récurrents en tête de liste
        usort($cast, function ($a, $b) {
            return $b['recurring_character'] <=> $a['recurring_character'];
        });

        return $cast;
    }

    public function updateTvCast($tv, $serieViewing): void
    {
        $recurringCharacters = $tv['credits']['cast'];
        $tvCast = $tv['credits']['cast'];

        foreach ($tv['seasons'] as $s) {
            $seasonNumber = $s['season_number'];
            if ($seasonNumber) { // 21/12/2022 : plus d'épisodes spéciaux
//                $season = $serieViewing->getSeasonByNumber($seasonNumber);
//                $episodeCount = $season->getEpisodeCount();
                $episodeCount = $s['episode_count'];

                for ($episodeNumber = 1; $episodeNumber <= $episodeCount; $episodeNumber++) {
                    $standing = $this->TMDBService->getTvEpisodeCredits($tv['id'], $seasonNumber, $episodeNumber, 'fr');
                    $credits = json_decode($standing, true);

                    if ($credits) {
                        if ($credits['cast']) {
                            foreach ($credits['cast'] as $cast) {
                                $recurringCharacter = $this->inTvCast($recurringCharacters, $cast['id']);
                                $this->episodesCast($cast, $seasonNumber, $episodeNumber, $recurringCharacter, false, $serieViewing);
                            }
                        }
                        if ($credits['guest_stars']) {
                            foreach ($credits['guest_stars'] as $guestStar) {
                                $this->episodesCast($guestStar, $seasonNumber, $episodeNumber, false, true, $serieViewing);
                            }
                        }
                    }
                }
            }
        }
        $this->serieCastRepository->saveAll();
    }

    public function inTvCast($tvCast, $personId): bool
    {
        foreach ($tvCast as $cast) {
            if ($cast['id'] == $personId) {
                return true;
            }
        }
        return false;
    }

    public function episodesCast($cast, $seasonNumber, $episodeNumber, $recurringCharacter, $guestStar, $serieViewing): void
    {
        $dbCast = $this->castRepository->findOneBy(['tmdbId' => $cast['id']]);
        $serieCast = null;
        if ($dbCast) {
            $serieCast = $this->serieCastRepository->findOneBy(['serieViewing' => $serieViewing, 'castId' => $dbCast->getId()]);
        }
        if ($serieCast === null) {
            $serieCast = $this->createSerieCast($cast, $dbCast, $recurringCharacter, $guestStar, $serieViewing);
        }
        $episodes = $serieCast->getEpisodes();
        if (!$this->episodeInSerieCastEpisodes($episodes, $seasonNumber, $episodeNumber)) {
            $serieCast->addEpisode($seasonNumber, $episodeNumber);
            $this->serieCastRepository->save($serieCast, true);
        }
    }

    public function episodeInSerieCastEpisodes($episodes, $seasonNumber, $episodeNumber): bool
    {
        foreach ($episodes as $season => $episode) {
            if ($season == $seasonNumber && $episode == $episodeNumber) {
                return true;
            }
        }
        return false;
    }

    public function createSerieCast($cast, $dbCast, $recurringCharacter, $guestStar, $serieViewing): SerieCast
    {
        if ($dbCast === null) {
            $dbCast = new Cast($cast['id'], $cast['name'], $cast['profile_path']);
            $this->castRepository->save($dbCast, true);
        }
        $serieCast = new SerieCast($serieViewing, $dbCast->getId());
        $serieCast->setKnownForDepartment($cast['known_for_department']);
        $serieCast->setCharacterName($cast['character']);
        $serieCast->setRecurringCharacter($recurringCharacter);
        $serieCast->setGuestStar($guestStar);
        $this->serieCastRepository->save($serieCast, true);
        $this->serieViewingRepository->save($serieViewing->addSerieCast($serieCast), true);

        return $serieCast;
    }

    public function whatsNew($tv, $serie, $serieRepository): array|null
    {
        $whatsNew = ['episode' => 0, 'season' => 0, 'status' => "", 'original_name' => ""];
        $modified = false;
        if ($serie->getNumberOfSeasons() !== $tv['number_of_seasons']) {
            $whatsNew['season'] = $tv['number_of_seasons'] - $serie->getNumberOfSeasons();
            $modified = true;

            $serie->setNumberOfSeasons($tv['number_of_seasons']);
            $serie->setUpdatedAt(new DateTime());
        }
        if ($serie->getNumberOfEpisodes() !== $tv['number_of_episodes']) {
            $whatsNew['episode'] = $tv['number_of_episodes'] - $serie->getNumberOfEpisodes();
            $modified = true;

            $serie->setNumberOfEpisodes($tv['number_of_episodes']);
            $serie->setUpdatedAt(new DateTime());
        }
        if ($serie->getStatus() !== $tv['status']) {
            $whatsNew['status'] = $tv['status'];
            $modified = true;

            $serie->setStatus($tv['status']);
            $serie->setUpdatedAt(new DateTime());
        }
        if ($serie->getOriginalName() !== $tv['original_name']) {
            $whatsNew['original_name'] = $tv['original_name'];
            $modified = true;

            $serie->setOriginalName($tv['original_name']);
        }
        /*
         * Si quelque chose a changé, l'enregistrement est mis à jour
         */
        if ($modified) {
            $serieRepository->save($serie, true);
            return $whatsNew;
        }

        return null;
    }

    #[Route(path: '/viewing', name: 'app_serie_viewing')]
    public function setSerieViewing(Request $request, TranslatorInterface $translator): Response
    {
        $serieId = $request->query->getInt('id');
        $season = $request->query->getInt('s');
        $episode = $request->query->getInt('e');
        $newValue = $request->query->getInt('v');
        $allBefore = $request->query->getInt('all');
        $liveWatch = $request->query->getInt('live');

        $user = $this->getUser();
        $serie = $this->serieRepository->find($serieId);
        $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
        $episodeViewings = $this->getEpisodeViewings($serieViewing, $season, $episode, $allBefore);
        $locale = $request->getLocale();

        /* ---start--- entity based viewing ---start--- */
        if ($newValue) {
            $deviceType = $request->query->getAlpha('device-type');
            $networkType = $request->query->getAlpha('network-type');
            /* Todo Review the network id */
            $networkId = $request->query->getInt('network-id');

            /** @var EpisodeViewing $episode */
            foreach ($episodeViewings as $episode) {

                if ($episode->getViewedAt() == null) {
                    $episodeTmdb = null;
                    if (!$episode->getAirDate()) {
                        $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);
                        if ($episodeTmdb) {
                            try {
                                $episode->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
                            } catch (Exception $e) {
                                $episode->setAirDate(null);
                            }
                        }
                    }

                    $episode->setNetworkId($networkId ?: null);
                    $episode->setNetworkType($networkType != "" ? $networkType : null);
                    $episode->setDeviceType($deviceType != "" ? $deviceType : null);
                    if ($liveWatch) {
                        if (!$episodeTmdb) {
                            $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);
                            if ($episodeTmdb) {
                                try {
                                    $episode->setViewedAt(new DateTimeImmutable($episodeTmdb['air_date']));
                                } catch (Exception $e) {
                                    $episode->setViewedAt(new DateTimeImmutable());
                                }
                            }
                        }
                    } else {
                        $episode->setViewedAt(new DateTimeImmutable());
                    }

                    $this->episodeViewingRepository->save($episode, true);
                }
            }
            /* ---start--- Serie / Season completed ? ---start--- */
            $this->viewingCompleted($serieViewing);
            /* ----end---- Season completed ? ----end---- */
        } else {
            /** @var EpisodeViewing $episode */
            $episode = $episodeViewings[0];

            $episode->setViewedAt(null);
            $episode->setDeviceType(null);
            $episode->setNetworkType(null);
            $episode->setNetworkId(null);
            if (!$episode->getAirDate()) {
                $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $season, $episode, $locale), true);
                if ($episodeTmdb) {
                    try {
                        $episode->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
                    } catch (Exception $e) {
                        $episode->setAirDate(new DateTimeImmutable());
                    }
                }
            }
            $this->episodeViewingRepository->save($episode, true);
            if ($episode->getSeason()->isSeasonCompleted()) {
                $episode->getSeason()->setSeasonCompleted(false);
                $this->seasonViewingRepository->save($episode->getSeason(), true);
            }
        }
        /*
         * Si "Épisodes vus au fur et à mesure" cochés, pour que la série reste à sa place
         * avec le tri "visionnage", ne pas mettre à jour le champ "modifiedAt"
         */
        if (!$liveWatch) {
            $serieViewing->setModifiedAt(new DateTime());
        } else {
            $createdAt = $this->getSerieViewingCreatedAt($serieViewing, $request);
            if ($createdAt) {
                $modifiedAt = new DateTime();
                $serieViewing->setModifiedAt($modifiedAt->setTimestamp($createdAt->getTimestamp()));
            }
        }
        $this->serieViewingRepository->save($serieViewing, true);
        $this->setViewedEpisodeCount($serieViewing);
        /* ----end---- entity based viewing ----end---- */

        $blocks = [];
        $globalIndex = 1;
        $viewed = 0;
        foreach ($serieViewing->getSeasons() as $seasonViewing) {
            if ($seasonViewing->getSeasonNumber()) { // 21/12/2022 : plus d'épisodes spéciaux
                $blocks[] = [
                    'season' => $seasonViewing->getSeasonNumber(),
                    'episode_count' => $seasonViewing->getEpisodeCount(),
                    'view' => $this->render('blocks/serie/_season_viewing.html.twig', [
                        'season' => $seasonViewing,
                        'globalIndex' => $globalIndex,
                    ])
                ];
                $viewed += $seasonViewing->getViewedEpisodeCount();
                $globalIndex += $seasonViewing->getEpisodeCount();
            }
        }

        $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $this->networks2Array($serie->getNetworks()), false, $locale);
        $blockNextEpisodeToWatch = $this->render('blocks/serie/_next_episode_to_watch.html.twig', [
            'nextEpisodeToWatch' => $nextEpisodeToWatch,
        ]);

        return $this->json([
            'blocks' => $blocks,
            'blockNextEpisodeToWatch' => $blockNextEpisodeToWatch,
            'viewedEpisodes' => $viewed,
            'episodeText' => $translator->trans($viewed > 1 ? "viewed episodes" : "viewed episode"),
            'seasonCompleted' => $serieViewing->getSeasonByNumber($season)->isSeasonCompleted(),
        ]);
    }

    public function networks2Array($networks): array
    {
        $networksArray = [];
        foreach ($networks as $network) {
            $netWorkArray['id'] = $network->getNetworkId();
            $networkArray['name'] = $network->getName();
            $networkArray['logoPath'] = $network->getLogoPath();
            $networkArray['originCountry'] = $network->getOriginCountry();
            $networkArray['networkId'] = $network->getNetworkId();
            $networksArray[] = $networkArray;
        }
        return $networksArray;
    }

    public function getSerieViewingCreatedAt($serieViewing, $request): DateTimeImmutable|null
    {
        $createdAt = $serieViewing->getCreatedAt();

        if ($createdAt) {
            return $createdAt;
        }

        $serie = $serieViewing->getSerie();

        if ($serie->getFirstDateAir()) {
            $createdAt = $serie->getFirstDateAir();
            if ($createdAt) {
                $serieViewing->setCreatedAt($createdAt);
                return $createdAt;
            }
        }
        $standing = $this->TMDBService->getTvEpisode($serie->getSerieId(), 1, 1, $request->getLocale());
        $firstEpisode = json_decode($standing, true);
        if ($firstEpisode && $firstEpisode['air_date']) {
            try {
                $createdAt = new DateTimeImmutable($firstEpisode['air_date']);
            } catch (Exception $e) {
                $createdAt = new DateTimeImmutable();
            }
            $serieViewing->setCreatedAt($createdAt);
            return $createdAt;
        }

        $standing = $this->TMDBService->getTv($serie->getSerieId(), $request->getLocale());
        $firstAirDate = json_decode($standing, true);
        if ($firstAirDate && $firstAirDate['first_air_date']) {
            try {
                $createdAt = new DateTimeImmutable($firstAirDate['first_air_date']);
            } catch (Exception $e) {
                $createdAt = new DateTimeImmutable();
            }
            $serieViewing->setCreatedAt($createdAt);
        }
        return $createdAt;
    }

    public function viewingCompleted(SerieViewing $serieViewing): void
    {
        $seasonsCompleted = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber()) {
                $completed = $season->getViewedEpisodeCount() == $season->getEpisodeCount();
                if ($completed && !$season->isSeasonCompleted()) {
                    $season->setSeasonCompleted(true);
                    $this->seasonViewingRepository->save($season, true);
                }
                if ($completed) $seasonsCompleted++;
            }
        }
        if ($serieViewing->getNumberOfSeasons() == 0) {
            $serieViewing->setNumberOfSeasons(count($serieViewing->getSeasons()));
            $this->serieViewingRepository->save($serieViewing, true);
        }
        if ($serieViewing->getNumberOfSeasons() == $seasonsCompleted) {
            $serieViewing->setSerieCompleted(true);
            $this->serieViewingRepository->save($serieViewing, true);
        }
    }

    public function setViewedEpisodeCount($serieViewing): void
    {
        $viewedEpisodeCount = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber()) { // 21/12/2022 : finito les épisodes spéciaux
                $viewedEpisodeCount += $season->getViewedEpisodeCount();
            }
        }
        $serieViewing->setViewedEpisodes($viewedEpisodeCount);
        $this->serieViewingRepository->save($serieViewing, true);
    }

    public function getEpisodeViewings(SerieViewing $serieViewing, int $seasonNumber, int $episodeNumber, bool $allBefore = false): array
    {
        $array = [];
        if ($allBefore) {
            if ($seasonNumber) {
                foreach ($serieViewing->getSeasons() as $season) {
                    if ($season->getSeasonNumber() > 0 && $season->getSeasonNumber() < $seasonNumber) { /* Season 1, ... season seasonNumber-1 */
                        $array = array_merge($array, $season->getEpisodes()->toArray());
                    }
                    if ($season->getSeasonNumber() == $seasonNumber) {
                        foreach ($season->getEpisodes() as $episode) {
                            if ($episode->getEpisodeNumber() <= $episodeNumber) {
                                $array[] = $episode;
                            }
                        }
                    }
                }
            }
        } else {
            $array[] = $serieViewing->getSeasonByNumber($seasonNumber)->getEpisodeByNumber($episodeNumber);
        }
        return $array;
    }

    public function mySerieIds(User $user): array
    {
        return array_map(function ($mySerieId) {
            return $mySerieId['serieId'];
        }, $this->serieRepository->findMySerieIds($user->getId()));
    }
}

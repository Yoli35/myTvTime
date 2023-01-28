<?php

namespace App\Controller;

use App\Entity\Cast;
use App\Entity\EpisodeViewing;
use App\Entity\SeasonViewing;
use App\Entity\Serie;
use App\Entity\SerieCast;
use App\Entity\SerieViewing;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\SerieSearchType;
use App\Repository\CastRepository;
use App\Repository\EpisodeViewingRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieCastRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Service\LogService;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
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
    const POPULAR = 'popular';
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const SEARCH = 'search';

    public function __construct(private readonly TMDBService              $TMDBService,
                                private readonly SerieRepository          $serieRepository,
                                private readonly ImageConfiguration       $imageConfiguration,
                                private readonly SerieViewingRepository   $serieViewingRepository,
                                private readonly SeasonViewingRepository  $seasonViewingRepository,
                                private readonly EpisodeViewingRepository $episodeViewingRepository,
                                private readonly CastRepository           $castRepository,
                                private readonly SerieCastRepository      $serieCastRepository,
                                private readonly TranslatorInterface      $translator,
                                private readonly LogService               $logService)
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
        if ($orderBy == 'modifiedAt') {
            $lastModifiedSerieViewings = $this->serieViewingRepository->findBy(['user' => $user], ['modifiedAt' => $order], $perPage, $perPage * ($page - 1));
            $results = array_map(function ($serieViewing) {
                return $serieViewing->getSerie();
            }, $lastModifiedSerieViewings);
        } else {
            $results = $serieRepository->findAllSeries($user->getId(), $page, $perPage, $orderBy, $order);
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

        foreach ($series as &$serie) {
            $serie['today'] = false;
            $viewing = $serie['viewing'];
            /** @var SerieViewing $viewing */
            if (!$viewing->isSerieCompleted()) {
                foreach ($viewing->getSeasons() as $season) {
//                    dump($serie['name'] . ": season " . $season->getSeasonNumber() . ' -> ' . $season->getAirAt()->format("d/m/Y"));
                    $diff = date_diff($now, $season->getAirAt());
                    if (!$season->isSeasonCompleted() && $diff->invert) {
                        foreach ($season->getEpisodes() as $episode) {
                            if ($episode->getAirDate()) {
                                $diff = date_diff($now, $episode->getAirDate());
                                if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0) {
                                    $serie['today'] = true;
//                                    dump($serie['name']);
                                }
                            }
                        }
                    }
                }
            }
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
                'order_by' => $orderBy,
                'order' => $order],
            'user' => $user,
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'leafSettings' => $leafSettings,
            'from' => self::MY_SERIES,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
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
        $date = $date->setTime(0, 0, 0);
        $now = new DateTimeImmutable();
        $now = $now->setTime(0, 0, 0);
        $diff = date_diff($now, $date);
        $delta = $diff->days;
//        dump($datetime, $diff, $delta);
        /** @var Serie[] $todayAirings */
        $todayAirings = $this->todayAiringSeries($date);
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

    public function todayAiringSeries($day): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $today = $day->setTime(0, 0, 0);
        $episodeViewings = $this->episodeViewingRepository->findBy(['airDate' => $today]);

        $seasonViewings = [];
        foreach ($episodeViewings as $episodeViewing) {
            $seasonViewings[] = [
                'seasonViewing' => $episodeViewing->getSeason(),
                'episodeViewing' => $episodeViewing,
                'episode_number' => $episodeViewing->getEpisodeNumber()
            ];
        }
        $serieViewings = [];
        foreach ($seasonViewings as $seasonViewing) {
            /** @var SeasonViewing $season */
            $season = $seasonViewing['seasonViewing'];
            $serieViewing = $season->getSerieViewing();
            if (!in_array($serieViewing, $serieViewings) && $serieViewing->getUser() === $user) {
                $serieViewings[] = [
                    'serieViewing' => $season->getSerieViewing(),
                    'seasonViewing' => $seasonViewing,
                    'season_number' => $season->getSeasonNumber()
                ];
            }
        }
//        dump($episodeViewings, $seasonViewings, $serieViewings);
        $todaySeries = [];
        foreach ($serieViewings as $serieViewing) {
            /** @var SerieViewing $s */
            $s = $serieViewing['serieViewing'];
            $season = $serieViewing['seasonViewing'];
            $season_number = $serieViewing['season_number'];
            $id = $s->getSerie()->getId();
            if (!key_exists($id, $todaySeries)) {
                $todaySeries[$id] = ['serie' => $s->getSerie(), 'serieViewing' => $serieViewing];
            }
            if (!key_exists('seasons', $todaySeries[$id])) {
                $todaySeries[$id]['seasons'] = [];
            }
            if (!key_exists($season_number, $todaySeries[$id]['seasons'])) {
                $todaySeries[$id]['seasons'][$season_number] = [];
            }
            if (!key_exists('episodes', $todaySeries[$id]['seasons'][$season_number])) {
                $todaySeries[$id]['seasons'][$season_number]['episodes'] = [];
            }
            $todaySeries[$id]['seasons'][$season_number]['episodes'][] = $season['episode_number'];
        }
//        dump($todaySeries);

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

    public function getSeriesViews($user, $results, $locale): array
    {
        $ids = array_map(function ($result) {
            return $result->getId();
        }, $results);
        $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $ids]);

        $series = [];
        /** @var Serie $result */
        foreach ($results as $result) {
            $serie = $this->serie2array($result, $locale);
            $serie['viewing'] = $this->getSerieViews($result, $serieViewings);

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

    public function serie2array(Serie $result, $locale): array
    {
        $tv = json_decode($this->TMDBService->getTv($result->getSerieId(), $locale), true);

//        if ($result->getStatus() != $tv['status']) {
//
//        }
        $serie['id'] = $result->getId();
        $serie['name'] = $result->getName();
        $serie['posterPath'] = $result->getPosterPath();
        $serie['backdropPath'] = $result->getBackdropPath();
        $serie['serieId'] = $result->getSerieId();
        $serie['firstDateAir'] = $result->getFirstDateAir();
        $serie['addedAt'] = $result->getAddedAt();
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
        $viewing->setSeasonCount($tv['number_of_seasons']);
        $viewing->setViewedEpisodes(0);
        $viewing->setSpecialEpisodes(count($tv['seasons']) > $tv['number_of_seasons']);
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
                    $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $i, 'fr', ['credits']);
                    $tmdbEpisode = json_decode($standing, true);
                    $episode = new EpisodeViewing($i, $tmdbEpisode ? $tmdbEpisode['air_date'] : null);
                    $season->addEpisode($episode);
                    $this->episodeViewingRepository->save($episode, true);

                    $credits = $tmdbEpisode['credits'];
//                    dump($credits);
                }
            }
        }
        return $viewing;
    }

    public function updateSerieViewing(SerieViewing $serieViewing, array $tv, ?Serie $serie): SerieViewing
    {
        if ($serieViewing->getSeasonCount() != $tv['number_of_seasons']) {
            $serieViewing->setSeasonCount($tv['number_of_seasons']);
//            $serieViewing->setModifiedAt(new DateTime());
            $this->serieViewingRepository->save($serieViewing, true);

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
                    }
                }
            }
        }
        $this->setViewedEpisodeCount($serieViewing);
        // Ajuste les champs seasonCount, seasonCompleted, serieCompleted
        $this->viewingCompleted($serieViewing);

        return $serieViewing;
    }

    public function addNewEpisode(array $tv, SeasonViewing $season, int $episodeNumber)
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

        return $this->render('serie/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
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
        } else {
            $serie['id'] = $userSerie->getSerieId();
            $serie['name'] = $userSerie->getName();
            $serie['backdropPath'] = $userSerie->getBackdropPath();
            $serie['firstDateAir'] = $userSerie->getFirstDateAir();
            $serie['posterPath'] = $userSerie->getPosterPath();
        }

        return $serie;
    }

    public function getSerie(Request $request, array $tv, int $page, string $from, $backId, Serie|null $serie, $query = "", $year = ""): Response
    {
        /** @var User $user */
        $user = $this->getUser();
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
                    $seasonsWithAView[] = $seasonWithAView;
                }
                $tv['seasons'] = $seasonsWithAView;
            }
        }
        $ygg = str_replace(' ', '+', $tv['name']);
        $yggOriginal = str_replace(' ', '+', $tv['original_name']);

//        $this->cleanCastTable();
//        dump($tv);

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
            'locale' => $request->getLocale(),
            'page' => $page,
            'from' => $from,
            'backId' => $backId,
            'query' => $query,
            'year' => $year,
            'user' => $user,
            'whatsNew' => $whatsNew,
            'viewedEpisodes' => $serieViewing?->getViewedEpisodes(),
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

//        dump($casts);

        foreach ($casts as $cast) {
            $castRepository->remove($cast);
        }
        $castRepository->flush();
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
            return $a['recurring_character'] < $b['recurring_character'];
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
                $season = $serieViewing->getSeasonByNumber($seasonNumber);
                $episodeCount = $season->getEpisodeCount();

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

    public function episodesCast($cast, $seasonNumber, $episodeNumber, $recurringCharacter, $guestStar, $serieViewing)
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

    /**
     * @throws Exception
     */
    #[Route(path: '/viewing', name: 'app_serie_viewing')]
    public function setSerieViewing(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

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
                            $episode->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
                        }
                    }

                    $episode->setNetworkId($networkId ?: null);
                    $episode->setNetworkType($networkType != "" ? $networkType : null);
                    $episode->setDeviceType($deviceType != "" ? $deviceType : null);
                    if ($liveWatch) {
                        if (!$episodeTmdb) {
                            $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);
                            if ($episodeTmdb) {
                                $episode->setViewedAt(new DateTimeImmutable($episodeTmdb['air_date']));
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
                $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $season, $episode, $request->getLocale()), true);
                if ($episodeTmdb) {
                    $episode->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
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
        }
        $this->serieViewingRepository->save($serieViewing);
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

        return $this->json([
            'blocks' => $blocks,
            'viewedEpisodes' => $viewed,
            'episodeText' => $translator->trans($viewed > 1 ? "viewed episodes" : "viewed episode"),
            'seasonCompleted' => $serieViewing->getSeasonByNumber($season)->isSeasonCompleted(),
        ]);
    }

    public function viewingCompleted(SerieViewing $serieViewing)
    {
        $seasonsCompleted = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            $completed = $season->getViewedEpisodeCount() == $season->getEpisodeCount();
            if ($completed && !$season->isSeasonCompleted()) {
                $season->setSeasonCompleted(true);
                $this->seasonViewingRepository->save($season, true);
            }
            if ($completed) $seasonsCompleted++;
        }
        if ($serieViewing->getSeasonCount() == 0) {
            $serieViewing->setSeasonCount(count($serieViewing->getSeasons()));
            $this->serieViewingRepository->save($serieViewing, true);
        }
        if ($serieViewing->getSeasonCount() == $seasonsCompleted) {
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

//    public function setSeason

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

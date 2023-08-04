<?php

namespace App\Controller;

use App\Entity\Alert;
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
use App\Repository\AlertRepository;
use App\Repository\CastRepository;
use App\Repository\EpisodeViewingRepository;
use App\Repository\FavoriteRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieCastRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Service\DateService;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/series', requirements: ['_locale' => 'fr|en|de|es'])]
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
    const EPISODES_OF_THE_DAY = 'today';
    const UPCOMING_EPISODES = 'upcoming_episodes';
    const UPCOMING_SERIES = 'upcoming_series';
    const POPULAR_SERIES = 'popular';
    const SEARCH_SERIES = 'search';
    const MY_EVENTS = 'my_events';

    public array $messages = [];

    public function __construct(private readonly AlertRepository          $alertRepository,
                                private readonly CastRepository           $castRepository,
                                private readonly DateService              $dateService,
                                private readonly EpisodeViewingRepository $episodeViewingRepository,
                                private readonly FavoriteRepository       $favoriteRepository,
                                private readonly ImageConfiguration       $imageConfiguration,
                                private readonly SeasonViewingRepository  $seasonViewingRepository,
                                private readonly SerieCastRepository      $serieCastRepository,
                                private readonly SerieRepository          $serieRepository,
                                private readonly SerieViewingRepository   $serieViewingRepository,
                                private readonly TMDBService              $TMDBService,
                                private readonly TranslatorInterface      $translator)
    {
    }

    #[Route('/', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, SettingsRepository $settingsRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

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
        $list = array_map(function ($serie) {
            $newSerie = [];
            $newSerie['id'] = $serie['id'];
            $newSerie['name'] = $serie['name'];
            $newSerie['original'] = $serie['original'];
            $newSerie['date'] = $serie['first_date_air'] ? $serie['first_date_air']->format('Y-m-d') : null;
            return $newSerie;
        }, $list);
        $totalResults = count($list);

        $imageConfig = $this->imageConfiguration->getConfig();
        $series = $totalResults ? $this->getSeriesViews($user, $results, $imageConfig, $request->getLocale()) : null;

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

        if ($series) {
            $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');

            foreach ($series as &$serie) {
                $serie = $this->isSerieAiringSoon($serie, $now);
            }
        }
//        dump($series);

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'numbers' => $serieRepository->numbers($user->getId())[0],
            'seriesList' => $list,
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
            'breadcrumb' => $this->breadcrumb(self::MY_SERIES),
            'from' => self::MY_SERIES,
            'imageConfig' => $imageConfig,
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
                return [$cookie['pp'], $cookie['ob'], $cookie['o']];
            } else {
                return [20, 'firstDateAir', 'desc'];
            }
        }
        return [$perPage, $sort, $order];
    }

    public function isSerieAiringSoon($serie, $now): array
    {
        if ($serie['status'] == 'In Production' || $serie['status'] == 'Planned') {
            $serie['prodStatus'] = $this->translator->trans($serie['status']);
            $serie['prodClass'] = $serie['status'] == 'In Production' ? 'in-production' : 'planned';
            return $serie;
        }
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
                            $serie['nextEpisode'] = sprintf("S%02dE%02d", $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber());
                            $findIt = true;
                        }
                    }
                    if (!$findIt) {
                        if ($episodeDiff->days) {
                            if ($episodeDiff->invert) {
                                $serie['passed'] = $episode->getAirDate()->format("m/d/Y");
                                if ($episodeDiff->y) {
                                    $serie['passedText'] = $this->translator->trans("available since") . " " . $episodeDiff->y . " " . $this->translator->trans($episodeDiff->y > 1 ? "years" : "year");
                                } else {
                                    if ($episodeDiff->m) {
                                        $serie['passedText'] = $this->translator->trans("available since") . " " . ($episodeDiff->m + +($episodeDiff->d > 20 ? 1 : 0)) . " " . $this->translator->trans($episodeDiff->m > 1 ? "months" : "month");
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

    public function getSeriesViews($user, $results, $imageConfig, $locale): array
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
            $serie['viewing'] = $this->getSerieViews($result->getId(), $serieViewings);
            $serie['favorite'] = $this->isFavorite($serie, $favorites);
            $serie['networks'] = $this->getNetworks($serie, $networks);

            if ($serie['posterPath'] && !file_exists("../public/images/series/posters" . $serie['posterPath'])) {
                $this->saveImageFromUrl(
                    $imageConfig['url'] . $imageConfig['poster_sizes'][3] . $serie['posterPath'],
                    "../public/images/series/posters" . $serie['posterPath']
                );
            }

            $series[] = $serie;
        }
        return $series;
    }

    public function getSerieViews($id, $serieViewings): ?SerieViewing
    {
        /** @var SerieViewing $serieViewing */
        foreach ($serieViewings as $serieViewing) {
            if ($serieViewing->getSerie()->getId() == $id) {
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

    public function saveImageFromUrl($imageUrl, $localeFile): bool
    {
        if (!file_exists($localeFile)) {

            // Vérifier si l'URL de l'image est valide
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                // Récupérer le contenu de l'image à partir de l'URL
                $imageContent = file_get_contents($imageUrl);

                // Ouvrir un fichier en mode écriture binaire
                $file = fopen($localeFile, 'wb');

                // Écrire le contenu de l'image dans le fichier
                fwrite($file, $imageContent);

                // Fermer le fichier
                fclose($file);

                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    #[Route('/today', name: 'app_series_today', methods: ['GET'])]
    public function today(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $day = $request->query->getInt('d');
        $week = $request->query->getInt('w');
        $month = $request->query->getInt('m');

        $datetime = $day . ' day ' . $week . ' week ' . $month . ' month';
        $date = $this->dateService->newDateImmutable($datetime, 'Europe/Paris');
        $date = $date->setTime(0, 0);
        $now = $this->dateService->newDateImmutable('now', 'Europe/Paris');
        $now = $now->setTime(0, 0);

        $diff = date_diff($now, $date);
        $delta = $diff->days;

        /** @var Serie[] $todayAirings */
//        $todayAirings = $this->todayAiringSeries($date);
        $todayAirings = $this->todayAiringSeriesV2($date);
//        dump($todayAirings);
        $backdrop = $this->getTodayAiringBackdrop($todayAirings);
        $images = $this->getNothingImages();

        return $this->render('series/today.html.twig', [
            'todayAirings' => $todayAirings,
            'date' => $date,
            'backdrop' => $backdrop,
            'images' => $images,
            'prev' => $delta * ($diff->invert ? -1 : 1),
            'next' => $delta * ($diff->invert ? -1 : 1),
            'breadcrumb' => $this->breadcrumb(self::EPISODES_OF_THE_DAY),
            'from' => self::EPISODES_OF_THE_DAY,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    public function todayAiringSeries(DateTimeImmutable $today): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $yesterday = $today->sub(new DateInterval('P1D'));

        $episodeViewingsDay = $this->episodeViewingRepository->findBy(['airDate' => $today]);
        $episodeViewingsDayBefore = $this->episodeViewingRepository->findBy(['airDate' => $yesterday]);
//        dump(["day" => $episodeViewingsDay], ["day before" => $episodeViewingsDayBefore]);
        $episodeViewings = array_merge($episodeViewingsDay, $episodeViewingsDayBefore);

        $episodesOfTheDay = [];
        foreach ($episodeViewings as $episodeViewing) {
            $episode = [];
            $episode['episodeNumbers'] = [];
            $serieViewing = $episodeViewing->getSeason()->getSerieViewing();

            if ($serieViewing->getUser()->getId() !== $user->getId())
                continue;

            $serie = $serieViewing->getSerie();
            $broadcastTheNextDay = $serieViewing->isTimeShifted();
            $airDate = $episodeViewing->getAirDate();
            $episodeNumber = $episodeViewing->getEpisodeNumber();
            $seasonNumber = $episodeViewing->getSeason()->getSeasonNumber();

            $episodesOfTheDayCount = count($episodesOfTheDay);
            for ($i = 0; $i < $episodesOfTheDayCount; $i++) {
                if ($episodesOfTheDay[$i]['serieId'] == $serie->getId() &&
                    $episodesOfTheDay[$i]['seasonNumber'] == $seasonNumber) {
                    $episodesOfTheDay[$i]['episodeNumbers'][] = $episodeNumber;
                    continue 2;
                }
            }
//            dump([
//                "broadcastTheNextDay" => $broadcastTheNextDay,
//                "episodeNumber" => $episodeNumber,
//                "seasonNumber" => $seasonNumber,
//                "airDate" => $airDate->format("d/m/Y"),
//                "yesterday" => $yesterday->format("d/m/Y"),
//                "today" => $today->format("d/m/Y")
//            ]);

            if (($broadcastTheNextDay && $airDate->format("d/m/Y") === $yesterday->format("d/m/Y")) ||
                (!$broadcastTheNextDay && $airDate->format("d/m/Y") === $today->format("d/m/Y"))) {
                $episode['airDate'] = $airDate;
                $episode['episodeNumbers'][] = $episodeNumber;
                $episode['seasonNumber'] = $seasonNumber;
                $episode['seasonEpisodeCount'] = $episodeViewing->getSeason()->getEpisodeCount();
                $episode['serieId'] = $serie->getId();
                $episode['serieName'] = $serie->getName();
                $episode['seriePosterPath'] = $serie->getPosterPath();
                $episodesOfTheDay[] = $episode;
            }
        }

        return $episodesOfTheDay;
    }

    public function todayAiringSeriesV2($date): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $today = $date->format('Y-m-d');
        $yesterday = $date->sub(new DateInterval('P1D'))->format('Y-m-d');

        $episodesOfTheDay = $this->serieViewingRepository->getEpisodesOfTheDay($user->getId(), $today, $yesterday, 1, 20);
        $episodesOfTheDayBySeries = [];

        foreach ($episodesOfTheDay as $episode) {
            $found = false;
            foreach ($episodesOfTheDayBySeries as &$ep) {
                if (!$found && $ep['serieId'] === $episode['serie_id'] && $ep['seasonNumber'] === $episode['season_number']) {
                    $ep['episodeNumbers'][] = $episode['episode_number'];
                    $ep['viewed'] = $episode['viewed'];
                    $found = true;
                }
            }
            if (!$found) {
                $episodesOfTheDayBySeries[] = [
                    'serieId' => $episode['serie_id'],
                    'serieName' => $episode['name'],
                    'seriePosterPath' => $episode['poster_path'],
                    'seasonNumber' => $episode['season_number'],
                    'seasonEpisodeCount' => $episode['episode_count'],
                    'episodeNumbers' => [$episode['episode_number']],
                    'viewed' => $episode['viewed'],
                ];
            }
        }
        return $episodesOfTheDayBySeries;
    }

    public function getTodayAiringBackdrop($airings): ?string
    {
        $index = rand(0, count($airings) - 1);
        $pos = 0;
        foreach ($airings as $airing) {
            if ($index === $pos++) {
                $serie = $this->serieRepository->find($airing['serieId']);
                return $serie->getBackdropPath();
            }
        }
        return null;
    }

    public function getNothingImages(): array
    {
        $images = scandir($this->getParameter('kernel.project_dir') . '/public/images/series/today');
        return array_slice($images, 2);
    }

    #[Route('/to-start', name: 'app_series_to_start', methods: ['GET'])]
    public function seriesToStart(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

        $page = $request->query->getInt('p', 1);
        $perPage = 10;
        $sort = 'createdAt';
        $order = 'DESC';

        /** @var User $user */
        $user = $this->getUser();
        $results = $this->serieViewingRepository->getSeriesToStartV2($user, $perPage, $page);

        $locale = $request->getLocale();
        $imageConfig = $this->imageConfiguration->getConfig();
        $seriesToBeStarted = $this->seriesToBeToArray($results, $imageConfig, $locale);

        $totalResults = $this->serieViewingRepository->count(['user' => $user, 'viewedEpisodes' => 0]);

        return $this->render('series/to_start.html.twig', [
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
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'breadcrumb' => $this->breadcrumb(self::MY_SERIES_TO_START),
            'from' => self::MY_SERIES_TO_START,
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/to-end', name: 'app_series_to_end', methods: ['GET'])]
    public function seriesToEnd(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $page = $request->query->getInt('p', 1);
        $perPage = 10;

        /** @var User $user */
        $user = $this->getUser();
        $results = $this->serieViewingRepository->getSeriesToEndV2($user->getId(), $perPage, $page);

        $locale = $request->getLocale();
        $imageConfig = $this->imageConfiguration->getConfig();
        $seriesToBeEnded = $this->seriesToBeToArray($results, $imageConfig, $locale);

        $totalResults = $this->serieViewingRepository->countUserSeriesToEnd($user);

//        $nextEpisodesToWatch = $this->serieViewingRepository->getNextEpisodesToWatch($user);
//        dump($nextEpisodesToWatch);

        return $this->render('series/to_end.html.twig', [
            'series' => $seriesToBeEnded,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY],
            'user' => $user,
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'breadcrumb' => $this->breadcrumb(self::MY_SERIES_TO_END),
            'from' => self::MY_SERIES_TO_END,
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/upcoming-episodes', name: 'app_series_upcoming_episodes', methods: ['GET'])]
    public function upcomingEpisodes(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $page = $request->query->getInt('p', 1);
        $perPage = 10;

        /** @var User $user */
        $user = $this->getUser();
        $results = $this->serieViewingRepository->getUpcomingEpisodes($user->getId(), $perPage, $page);
        $totalResults = $this->serieViewingRepository->countUpcomingEpisodes($user->getId());

        $imageConfig = $this->imageConfiguration->getConfig();

        $results = array_map(function ($result) use ($request, $imageConfig) {
            // on rajoute les networks
            $tv = json_decode($this->TMDBService->getTv($result['tmdb_id'], $request->getLocale()), true);
            $result['networks'] = $tv ? $tv['networks'] : [];

            // Date et épisode. Ex : Demain /  S01E01
            $now = $this->dateService->newDate('now', "Europe/Paris", true);
            $date = $this->dateService->newDate($result['air_date'], "Europe/Paris");
            if ($result['time_shifted']) $date->add(new DateInterval('P1D'));
            $diff = $now->diff($date);
            $result['date'] = $date->format('Y-m-d');
//            dump([
//                'now' => $now->format('Y-m-d'),
//                'date' => $date->format('Y-m-d'),
//                'diff' => $diff->days,
//                'time_shifted' => $result['time_shifted'],
//                'air_date' => $result['air_date'],
//            ]);
            if ($now->getTimestamp() === $date->getTimestamp()) {
                $result['air_date'] = 'Today';
                $result['air_date_relative'] = true;
                $result['class'] = "today";
            } elseif ($diff->days == 1 && $diff->invert == 0) {
                $result['air_date'] = 'Tomorrow';
                $result['air_date_relative'] = true;
                $result['class'] = "tomorrow";
            } elseif ($diff->days == 2 && $diff->invert == 0) {
                $result['air_date'] = 'The day after tomorrow';
                $result['air_date_relative'] = true;
                $result['class'] = "after-tomorrow";
            } elseif ($diff->days < 7 && $diff->invert == 0) {
                $result['air_date'] = $this->translator->trans($date->format('l'));
                $result['air_date'] .= "<div>" . $this->translator->trans('in') . " " . $diff->d . " " . $this->translator->trans('days') . "</div>";
                $result['class'] = "this-week";
            } else {
                $result['air_date'] = $this->dateService->formatDate($date, "Europe/Paris", $request->getLocale());
                if ($diff->y) {
                    $result['air_date'] .= "<div>" . $this->translator->trans($diff->invert ? 'in-invert' : 'in') . " " . $diff->y . " " . $this->translator->trans($diff->y > 1 ? 'years' : 'year') . "</div>";
                } elseif ($diff->m) {
                    $result['air_date'] .= "<div>" . $this->translator->trans($diff->invert ? 'in-invert' : 'in') . " " . $diff->m . " " . $this->translator->trans($diff->m > 1 ? 'months' : 'month');
                    if ($diff->d) $result['air_date'] .= " " . $this->translator->trans('and') . " " . $diff->d . " " . $this->translator->trans($diff->d > 1 ? 'days' : 'day');
                    $result['air_date'] .= "</div>";
                } elseif ($diff->d) {
                    $result['air_date'] .= "<div>" . $this->translator->trans($diff->invert ? 'in-invert' : 'in') . " " . $diff->d . " " . $this->translator->trans($diff->d > 1 ? 'days' : 'day') . "</div>";
                }
                $result['class'] = "later";
            }
            $result['episode'] = sprintf('S%02dE%02d', $result['season_number'], $result['episode_number']);

            // Nouvelle saison, première, dernière, etc.
            if ($result['season_number'] == 1 && $result['episode_number'] == 1) {
                $result['event'] = 'Premiere';
            } elseif ($result['season_number'] && $result['episode_number'] == 1) {
                $result['event'] = 'New season';
            } elseif ($result['season_number'] == $result['season_count'] && $result['episode_number'] == $result['episode_count']) {
                $result['event'] = 'Finale';
            } elseif ($result['episode_number'] == $result['episode_count']) {
                $result['event'] = 'Last episode of the season';
            }
            $this->savePoster($result['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
            return $result;
        }, $results);

        // L'option J+1 peut casser l'ordre chronologique, on le rétablit
        uksort($results, function ($a, $b) use ($results) {
            return $results[$a]['date'] <=> $results[$b]['date'];
        });

        return $this->render('series/upcoming-episodes.html.twig', [
            'series' => $results,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY],
            'user' => $user,
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'breadcrumb' => $this->breadcrumb(self::UPCOMING_EPISODES),
            'from' => self::UPCOMING_EPISODES,
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/upcoming-series', name: 'app_series_upcoming_series', methods: ['GET'])]
    public function upcomingSeries(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $page = $request->query->getInt('p', 1);
        $perPage = 10;

        /** @var User $user */
        $user = $this->getUser();
        $results = $this->serieViewingRepository->upcomingSeries($user->getId(), $perPage, $page);
        $totalResults = $this->serieViewingRepository->countUpcomingSeries($user->getId());
        $imageConfig = $this->imageConfiguration->getConfig();

        $lastResult = null;
        $results = array_map(function ($result) use ($imageConfig, &$lastResult) {
            if (!key_exists('networks', $result)) {
                $result['networks'][] = ['name' => $result['network_name'], 'logo_path' => $result['network_logo_path']];
            }
            // on factorise les networks
            if ($lastResult && $lastResult['id'] == $result['id']) {
                $lastResult['networks'] = array_merge($lastResult['networks'], $result['networks']);
                return null;
            }
            $lastResult = $result;
            if ($result['status'] == 'In Production' || $result['status'] == 'Planned') {
                $result['prodStatus'] = $this->translator->trans($result['status']);
                $result['prodClass'] = $result['status'] == 'In Production' ? 'in-production' : 'planned';
            }
            $this->savePoster($result['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);

            return $result;
        }, $results);

//        dump($results);

        return $this->render('series/upcoming-series.html.twig', [
            'series' => $results,
            'pages' => [
                'total_results' => $totalResults,
                'page' => $page,
                'per_page' => $perPage,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
                'per_page_values' => self::PER_PAGE_ARRAY],
            'user' => $user,
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'breadcrumb' => $this->breadcrumb(self::UPCOMING_SERIES),
            'from' => self::UPCOMING_SERIES,
            'imageConfig' => $imageConfig,
        ]);
    }

    function getPosters(): array|false
    {
        $posterFiles = scandir($this->getParameter('kernel.project_dir') . '/public/images/series/posters');
        /*
         * 0 => "."
         * 1 => ".."
         * 2 => ".DS_Store"
         */
        if (($posterFiles[2] === '.DS_Store') || ($posterFiles[2] === 'Thumbs.db'))
            return array_slice($posterFiles, 3);
        else
            return array_slice($posterFiles, 2);
    }

    public function seriesToBeToArray($results, $imageConfig, $locale): array
    {
        $serieViewingIds = array_map(function ($result) {
            return $result['id'];
        }, $results);
        $serieViewings = $this->serieViewingRepository->findBy(['id' => $serieViewingIds]);
        $seriesToBe = array_map(function ($result) use ($serieViewings, $serieViewingIds, $imageConfig, $locale) {
            $serie = $this->serie2arrayV2($result, $locale);
            $serie['viewing'] = $this->getSerieViewing($result['id'], $serieViewings);
            $this->savePoster($serie['posterPath'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
            return $serie;
        }, $results);

        $now = new DateTime();
        $now->setTime(0, 0);
        foreach ($seriesToBe as &$serie) {
            $serie = $this->isSerieAiringSoon($serie, $now);
        }
//        dump([
//            'seriesToBeV2' => $seriesToBe,
//        ]);

        return $seriesToBe;
    }

    public function serie2arrayV2($result, $locale): array
    {
        $tv = json_decode($this->TMDBService->getTv($result['tmdb_id'], $locale), true);

        $serie['id'] = $result['serie_id']; //getId();
        $serie['name'] = $result['name']; //getName();
        $serie['posterPath'] = $result['poster_path']; //getPosterPath();
        $serie['backdropPath'] = $result['backdrop_path']; //getBackdropPath();
        $serie['serieId'] = $result['tmdb_id']; //getSerieId();
        $serie['firstDateAir'] = $result['first_date_air']; //getFirstDateAir();
        $serie['createdAt'] = $result['serie_created_at']; //getCreatedAt();
        $serie['updatedAt'] = $result['serie_updated_at']; //getUpdatedAt();
        $serie['status'] = $result['serie_status']; //getStatus();
        $serie['overview'] = $result['overview']; //getOverview();
        $serie['numberOfEpisodes'] = $result['number_of_episodes']; //getNumberOfEpisodes();
        $serie['numberOfSeasons'] = $result['number_of_seasons']; //getNumberOfSeasons();
        $serie['originalName'] = $result['original_name']; //getOriginalName();

        $serie['favorite'] = $result['favorite'];

        if ($tv) {
            $serie['networks'] = array_map(function ($network) use ($result) {
                $network['networkId'] = $network['id'];
                $network['logoPath'] = $network['logo_path'];
                $network['serieId'] = $result['serie_id'];
                return $network;
            }, $tv['networks']);

            $serie['tmdb_status'] = $tv['status'];
            $serie['tmdb_next_episode_to_air'] = $tv['next_episode_to_air'];
        } else {
            $serie['networks'] = [];
            $serie['tmdb_status'] = 'Not found';
            $serie['tmdb_next_episode_to_air'] = null;
        }

        return $serie;
    }

    public function getSerieViewing(int $id, array $serieViewings): SerieViewing|null
    {
        foreach ($serieViewings as $serieViewing) {
            if ($serieViewing->getId() === $id) {
                return $serieViewing;
            }
        }
        return null;
    }

    public function savePoster($posterPath, $posterUrl): void
    {
        if (!file_exists("../public/images/series/posters" . $posterPath)) {
            $this->saveImageFromUrl(
                $posterUrl . $posterPath,
                "../public/images/series/posters" . $posterPath
            );
        }
    }

    #[Route('/search/{page}', name: 'app_series_search', defaults: ['page' => 1], methods: ['GET', 'POST'])]
    public function search(Request $request, int $page): Response
    {
//        $this->logService->log($request, $this->getUser());
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
        return $this->render('series/search.html.twig', [
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
            'breadcrumb' => $this->breadcrumb(self::SEARCH_SERIES),
            'from' => self::SEARCH_SERIES,
            'imageConfig' => $this->imageConfiguration->getConfig(),
        ]);
    }

    #[Route('/popular', name: 'app_series_popular', methods: ['GET'])]
    public function popular(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();

        $standing = $this->TMDBService->getSeries(self::POPULAR_SERIES, $page, $locale);
        $series = json_decode($standing, true);
        $imageConfig = $this->imageConfiguration->getConfig();

        foreach ($series['results'] as $serie) {
            $this->savePoster($serie['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
        }

        $breadcrumb = $this->breadcrumb(self::POPULAR_SERIES);

        return $this->render('series/popular.html.twig', [
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($user) : [],
            'pages' => [
                'total_results' => $series['total_results'],
                'page' => $page,
                'per_page' => 20,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'breadcrumb' => $breadcrumb,
            'from' => self::POPULAR_SERIES,
            'user' => $user,
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'imageConfig' => $this->imageConfiguration->getConfig(),
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
                $airDate = $s['air_date'] ? $this->dateService->newDateImmutable($s['air_date'], 'Europe/Paris', true) : null;
                $season = new SeasonViewing($airDate, $s['season_number'], $s['episode_count'], false);
                $viewing->addSeason($season);
                $this->seasonViewingRepository->save($season, true);

                for ($i = 1; $i <= $season->getEpisodeCount(); $i++) {
                    $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $i, 'fr');
                    $tmdbEpisode = json_decode($standing, true);
                    $episode = new EpisodeViewing($i, $season, $tmdbEpisode ? $tmdbEpisode['air_date'] : null);
                    $season->addEpisode($episode);
                    $this->episodeViewingRepository->save($episode, true);
                }
            }
        }
        $firstEpisodeViewing = $this->getSeasonViewing($viewing, 1)->getEpisodeByNumber(1);
        $airDate = $firstEpisodeViewing?->getAirDate();
        if ($airDate) {
            $now = $this->dateService->newDate('now', 'Europe/Paris', true);
            $diff = $now->diff($airDate);
        }
        if (!$airDate || ($diff->invert == 1 && $diff->days > 7)) {
            $viewing->setNextEpisodeToWatch(null);
            $viewing->setNextEpisodeToAir(null);
        } else {
            $viewing->setNextEpisodeToWatch($firstEpisodeViewing);
            $viewing->setNextEpisodeToAir($firstEpisodeViewing);
        }
        return $viewing;
    }

    public function updateSerieViewing(SerieViewing $serieViewing, array $tv, ?Serie $serie, bool $verbose = false): SerieViewing
    {
        $modified = false;
        if ($serieViewing->getNumberOfSeasons() != $tv['number_of_seasons']) {
            $serieViewing->setNumberOfSeasons($tv['number_of_seasons']);
            $serieViewing->setSerieCompleted(false);
            $modified = true;
        }
        if ($serieViewing->getNumberOfEpisodes() != $tv['number_of_episodes']) {
            $serieViewing->setNumberOfEpisodes($tv['number_of_episodes']);
            $serieViewing->setSerieCompleted(false);
            $modified = true;
        }
        if ($serieViewing->isSerieCompleted() == NULL) {
            $serieViewing->setSerieCompleted(false);
            $modified = true;
        }
        if ($serieViewing->getCreatedAt() == null) {
            $serieViewing->setCreatedAt($this->dateService->newDateImmutable($tv['first_air_date'], 'Europe/Paris'));
            $modified = true;
        }
        if ($serieViewing->getModifiedAt() == null) {
            $serieViewing->setModifiedAt($this->dateService->newDate($tv['first_air_date'], 'Europe/Paris'));
            $modified = true;
        }
        if ($modified) {
            $this->serieViewingRepository->save($serieViewing, true);
        }

        foreach ($tv['seasons'] as $s) {
            if ($s['season_number'] == 0) continue; // 21/12/2022 : plus d'épisodes spéciaux

            $season = $serieViewing->getSeasonByNumber($s['season_number']);
            if ($season === null) {
                $airDate = $s['air_date'] ? $this->dateService->newDateImmutable($s['air_date'], 'Europe/Paris', true) : null;
                $season = new SeasonViewing($airDate, $s['season_number'], $s['episode_count'], false);
                $this->seasonViewingRepository->save($season, true);
                $serieViewing->addSeason($season);
                for ($i = 1; $i <= $s['episode_count']; $i++) {
                    $this->addNewEpisode($tv, $season, $i);
                }
            } else {
                if ($season->getEpisodeCount() < $s['episode_count']) {
                    for ($i = $season->getEpisodeCount() + 1; $i <= $s['episode_count']; $i++) {
                        $this->addNewEpisode($tv, $season, $i);
                    }
                }
                // TODO : remove episodes
//                else {
//                    for ($i = $s['episode_count'] + 1; $i <= $season->getEpisodeCount(); $i++) {
//                        $episode = $season->getEpisodeByNumber($i);
//                        if ($episode !== null) {
//                            $season->removeEpisodeViewing($episode);
////                            $this->episodeViewingRepository->remove($episode, true);
//                        }
//                    }
//                }
                $season->setEpisodeCount($s['episode_count']);
            }
            $this->seasonViewingRepository->save($season, true);

            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getAirDate() === null) {
                    $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $episode->getEpisodeNumber(), 'fr');
                    $tmdbEpisode = json_decode($standing, true);
                    if ($tmdbEpisode && key_exists('air_date', $tmdbEpisode) && $tmdbEpisode['air_date']) {
                        $episode->setAirDate($this->dateService->newDateImmutable($tmdbEpisode['air_date'], 'Europe/Paris'));
                        $this->episodeViewingRepository->save($episode, true);
                        if (!$verbose) $this->addFlash('success', 'Date mise à jour : ' . $tmdbEpisode['air_date']);
                    }
                }
            }
        }
        $this->setViewedEpisodeCount($serieViewing);
        // Ajuste les champs seasonCount, seasonCompleted, serieCompleted
        // Et si la série n'est pas terminée, on met à jour le prochain épisode à regarder
        if (!$this->viewingCompleted($serieViewing)) {
            $this->setNextEpisode($tv, $serieViewing, $verbose);
        }

        return $serieViewing;
    }

    public function setNextEpisode($tv, $serieViewing, $verbose = false): void
    {
        $nextEpisodeCheck = false;
        if ($verbose) $messages = ['    Next episode to air: none', '    Next episode to watch: none', ''];
        if ($tv['next_episode_to_air'] === null) {
            $serieViewing->setNextEpisodeToAir(null);
        } else {
            $nextEpisode = $tv['next_episode_to_air'];
            $nextEpisodeNumber = $nextEpisode['episode_number'];
            $nextSeasonNumber = $nextEpisode['season_number'];
            $episode = $this->getSeasonViewing($serieViewing, $nextSeasonNumber)?->getEpisodeByNumber($nextEpisodeNumber);
            $serieViewing->setNextEpisodeToAir($episode);
            $nextEpisodeCheck = true;
            if ($verbose) {
                $messages[0] = sprintf('    Next episode to air: S%02dE%02d', $nextSeasonNumber, $nextEpisodeNumber);
                if (!$episode) $messages[0] .= ' (not in database)';
            }
        }
        $serieViewing->setNextEpisodeToWatch(null);
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber() === 0) continue;
            if ($season->isSeasonCompleted()) continue;
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->isViewed()) continue;
                $serieViewing->setNextEpisodeToWatch($episode);
                $nextEpisodeCheck = true;
                if ($verbose) $messages[1] = sprintf('    Next episode to watch: S%02dE%02d', $season->getSeasonNumber(), $episode->getEpisodeNumber());
                break 2;
            }
        }
        if ($serieViewing->getNextEpisodeToAir()?->isViewed()) {
            $serieViewing->setNextEpisodeToAir($serieViewing->getNextEpisodeToWatch());
            $nextEpisodeCheck = true;
            if ($verbose) $messages[1] = '    Next episode to air is viewed, set to next episode to watch if any';
        }
        if ($nextEpisodeCheck)
            $serieViewing->setNextEpisodeCheckDate($this->dateService->newDate('now', 'Europe/Paris'));
        $this->serieViewingRepository->save($serieViewing, true);
        if ($verbose) $this->messages = $messages;
    }

    public function addNewEpisode(array $tv, SeasonViewing $season, int $episodeNumber): void
    {
        $standing = $this->TMDBService->getTvEpisode($tv['id'], $season->getSeasonNumber(), $episodeNumber, 'fr');
        $tmdbEpisode = json_decode($standing, true);
//        dump(['tv' => $tv, 'seasonViewing' => $season, 'tmdbEpisode' => $tmdbEpisode]);
        $episode = new EpisodeViewing($episodeNumber, $season, $tmdbEpisode ? $tmdbEpisode['air_date'] : null);
        $season->addEpisode($episode);
        $this->episodeViewingRepository->save($episode, true);
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

    #[Route('/show/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

        $tmdbService = $this->TMDBService;

        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']);
        if ($standing == "") {
            return $this->render('series/error.html.twig', [
                'serie' => $serie,
                'serieViewing' => $this->serieViewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]),
                'imageConfig' => $this->imageConfiguration->getConfig(),
            ]);
        }
        $tv = json_decode($standing, true);

        return $this->getSerie($request, $tv, $page, $from, $serie->getId(), $serie, $query, $year);
    }

    #[Route('/tmdb/{id}', name: 'app_series_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id): Response
    {
//        $this->logService->log($request, $this->getUser());
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $this->TMDBService->getTv($id, $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']);
        if ($standing == "") {
            $serie = $this->serieRepository->findOneBy(['serieId' => $id]);
            return $this->render('series/error.html.twig', [
                'serie' => $serie,
                'serieViewing' => $this->serieViewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]),
                'imageConfig' => $this->imageConfiguration->getConfig(),
            ]);
        }
        $tv = json_decode($standing, true);

        return $this->getSerie($request, $tv ?? [], $page, $from, $id, null, $query, $year);
    }

    #[Route('/tmdb/{id}/season/{seasonNumber}', name: 'app_series_tmdb_season', methods: ['GET'])]
    public function season(Request $request, $id, $seasonNumber): Response
    {
        $imgConfig = $this->imageConfiguration->getConfig();

        $from = $request->query->get('from');
        $page = $request->query->get('p');
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');

        // Cookie pour le layout
        $seasonsCookie = $this->seasonsCookie($request);

        // La série (db ou the movie db) et sa bannière
        $serie = $this->serie($id, $request->getLocale());
        $serie['backdropPath'] = $this->fullUrl('backdrop', 3, $serie['backdropPath'], 'no_banner_dark.png', $imgConfig);

        // La saison (db ou the movie db) et son affiche (poster)
        $locale = $request->getLocale();
        $language = ['fr' => 'fr-FR', 'en' => 'en-US', 'es' => 'es-SP', 'de' => 'de-DE'][$locale];
        $standing = $this->TMDBService->getTvSeason($id, $seasonNumber, $language, ['credits', 'watch/providers']);
        $season = json_decode($standing, true);

        // Si les données de la saison ne sont pas disponibles dans la langue de l'utilisateur, on les prend en anglais
        $localized = true;
        if (!$this->isThereSomeOverviews($season)) {
            $standing = $this->TMDBService->getTvSeason($id, $seasonNumber, '', ['credits', 'watch/providers']);
            $internationalSeason = json_decode($standing, true);
            if ($this->isThereSomeOverviews($internationalSeason)) {
                $internationalSeason['watch/providers'] = $season['watch/providers'];
                $season = $internationalSeason;
                $localized = false;
            }
        }

        $credits = $season['credits'];
        if (!key_exists('cast', $credits)) {
            $credits['cast'] = [];
        }
        $season['poster_path'] = $this->fullUrl('poster', 3, $season['poster_path'], 'no_poster.png', $imgConfig);

        // Les épisodes (the movie db), l'ajustement de la date (J+1 ?) et leurs affiches (still)
        $episodes = [];
        $isShifted = $serie['userSerieViewing'] && $serie['userSerieViewing']->isTimeShifted();
        foreach ($season['episodes'] as $episode) {
            if ($isShifted) {
                if ($episode['air_date']) {
                    $airDate = $this->dateService->newDate($episode['air_date'], "Europe/Paris")?->modify('+1 day')->format('Y-m-d');
                    $episode['air_date'] = $airDate;
                }
            }
            $episode['still_path'] = $this->fullUrl('still', 3, $episode['still_path'], 'no_poster.png', $imgConfig);

            $episodes[] = $episode;
            if (key_exists('cast', $episode)) {
                foreach ($episode['cast'] as $cast) {
                    if (!in_array($cast, $credits['cast'])) {
                        $credits['cast'][] = $cast;
                    }
                }
            }
            if (key_exists('guest_stars', $episode)) {
                foreach ($episode['guest_stars'] as $cast) {
                    if (!in_array($cast, $credits['cast'])) {
                        $credits['cast'][] = $cast;
                    }
                }
            }
        }
        // Ajout de l'url des profils des acteurs et de l'équipe technique
        foreach ($credits['cast'] as $key => $cast) {
            $credits['cast'][$key]['profile_path'] = $this->fullUrl('profile', 2, $cast['profile_path'], 'no_profile.png', $imgConfig);
        }
        if (key_exists('crew', $credits)) {
            foreach ($credits['crew'] as $key => $crew) {
                $credits['crew'][$key]['profile_path'] = $this->fullUrl('profile', 2, $crew['profile_path'], 'no_profile.png', $imgConfig);
            }
        }
        // Les détails liés à l'utilisateur/spectateur (série, saison, épisodes)
        $modifications = [];
        if ($serie['userSerieViewing']) {
            // les infos de la saison
            $seasonViewing = $this->getSeasonViewing($serie['userSerieViewing'], $seasonNumber);
            // les infos des épisodes
            $episodes = array_map(function ($episode) use ($seasonViewing, $isShifted) {
                $episodeViewing = $this->getEpisodeViewing($seasonViewing, $episode['episode_number']);
                $episode['viewing'] = $episodeViewing;
                return $episode;
            }, $episodes);
        } else {
            $seasonViewing = null;
        }

        $episodesVotes = [];
        foreach ($episodes as $episode) {
            if (array_key_exists('viewing', $episode)) {
                /** @var EpisodeViewing $episodeViewing */
                $episodeViewing = $episode['viewing'];
                // La date de diffusion peut changer par rapport au calendrier initial de la série / saison
                $airDate = $this->dateService->newDateImmutable($episode['air_date'], "Europe/Paris", true)->modify($isShifted ? "-1 day" : "0 day");
                if ($episodeViewing->getAirDate()->format('Y-m-d') != $airDate->format('Y-m-d')) {
                    $modifications[] = [
                        'episode' => $episode['episode_number'],
                        'episode air date' => $episode['air_date'],
                        'airDate' => $airDate,
                        'viewing air date' => $episodeViewing->getAirDate(),
                    ];
                    $episodeViewing->setAirDate($airDate);
                    $this->episodeViewingRepository->save($episodeViewing, true);
                }
                $episodesVotes[] = ['number' => $episodeViewing->getEpisodeNumber(), 'vote' => $episodeViewing->getVote()];
            }
        }

        // Les fournisseurs de streaming français de la série
        $watchProviders = $season['watch/providers'];
        $watchProviders = array_key_exists("FR", $watchProviders['results']) ? $watchProviders['results']["FR"] : null;

        // if there is no data for the season, we try to get the data for the serie
        // May occurs for a newly added season 2+ of a serie
        if (!$watchProviders) {
            $watchProviders = $this->TMDBService->getTvWatchProviders($id);
            $watchProviders = json_decode($watchProviders, true);
            $watchProviders = array_key_exists("FR", $watchProviders['results']) ? $watchProviders['results']["FR"] : null;
        }

        if ($watchProviders) {
            $array1 = $this->getProviders($watchProviders, 'buy', $imgConfig, []);
            $array2 = $this->getProviders($watchProviders, 'flatrate', $imgConfig, $array1);
            $array3 = $this->getProviders($watchProviders, 'free', $imgConfig, $array2);
            $watchProviders = array_filter($array3);
        }

        // Breadcrumb
        $breadcrumb = $this->breadcrumb($from, $serie, $season, null);

//        dump([
//            'season' => $season,
//            'modifications' => $modifications,
//            'watchProviders' => $watchProviders,
//        ]);

        return $this->render('series/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'seasonViewing' => $seasonViewing,
            'episodes' => $episodes,
            'episodesVotes' => $episodesVotes,
            'credits' => $credits,
            'watchProviders' => $watchProviders,
            'seasonsCookie' => $seasonsCookie,
            'modifications' => $modifications,
            'localized' => $localized,
            'breadcrumb' => $breadcrumb,
            'parameters' => [
                'from' => $from,
                'page' => $page,
                'query' => $query,
                'year' => $year,
                "backId" => $backId
            ],
        ]);
    }

    public function breadcrumb($from, $serie = null, $season = null, $episode = null): array
    {
        switch ($from) {
            case self::MY_SERIES:
                $baseUrl = $this->generateUrl("app_series_index");
                $baseName = $this->translator->trans("My series");
                break;
            case self::MY_SERIES_TO_START:
                $baseUrl = $this->generateUrl("app_series_to_start");
                $baseName = $this->translator->trans("My series to start");
                break;
            case self::MY_SERIES_TO_END:
                $baseUrl = $this->generateUrl("app_series_to_end");
                $baseName = $this->translator->trans("My series to end");
                break;
            case self::EPISODES_OF_THE_DAY:
                $baseUrl = $this->generateUrl("app_series_today");
                $baseName = $this->translator->trans("My series airing today");
                break;
            case self::UPCOMING_EPISODES:
                $baseUrl = $this->generateUrl("app_series_upcoming_episodes");
                $baseName = $this->translator->trans("Upcoming episodes");
                break;
            case self::UPCOMING_SERIES:
                $baseUrl = $this->generateUrl("app_series_upcoming_series");
                $baseName = $this->translator->trans("Upcoming series");
                break;
            case self::SEARCH_SERIES:
                $baseUrl = $this->generateUrl("app_series_search");
                $baseName = $this->translator->trans("Series search");
                break;
            case self::MY_EVENTS:
                $baseUrl = $this->generateUrl("app_event");
                $baseName = $this->translator->trans("My events");
                break;
            case self::POPULAR_SERIES:
            default:
                $baseUrl = $this->generateUrl("app_series_popular");
                $baseName = $this->translator->trans("Popular series");
                break;
        }

        $breadcrumb = [
            [
                'name' => $baseName,
                'url' => $baseUrl,
            ]
        ];
        if ($serie) {
            $breadcrumb[] = [
                'name' => $serie['name'],
                'url' => $this->generateUrl('app_series_tmdb', ['id' => $serie['id']]) . '?from=' . $from,
            ];
        }
        if ($season) {
            $breadcrumb[] = [
                'name' => $this->translator->trans('Season') . ' ' . $season['season_number'],
                'url' => $this->generateUrl('app_series_tmdb_season', ['id' => $serie['id'], 'seasonNumber' => $season['season_number']]) . '?from=' . $from,
            ];
        }
        if ($episode) {
            $breadcrumb[] = [
                'name' => $this->translator->trans('Episode') . ' ' . $episode['episode_number'],
                'url' => $this->generateUrl('series_episode', ['id' => $serie['id'], 'seasonNumber' => $season['season_number'], 'episodeNumber' => $episode['episode_number']]),
            ];
        }
        return $breadcrumb;
    }

    public function seasonsCookie(Request $request): array
    {
        if (isset($_COOKIE['series_seasons'])) {
            $seasonsCookie = json_decode($_COOKIE['series_seasons'], true);
//            dump(['$_COOKIE' => $_COOKIE, 'get seasonsCookie' => $seasonsCookie]);
        } else {
            $seasonsCookie = ['layout' => 'list'];
            $arr_cookie_options = [
                'expires' => strtotime('+1 year'),
                'path' => '/',
//                'domain' => '.example.com', // leading dot for compatibility or use subdomain
//                'secure' => true,     // or false
//                'httponly' => true,    // or false
//                'samesite' => 'Lax' // None || Lax  || Strict
            ];
            setcookie('series_seasons', json_encode($seasonsCookie), $arr_cookie_options);
//            dump(['$_COOKIE' => $_COOKIE, 'set seasonsCookie' => $seasonsCookie]);
        }
        return $seasonsCookie;
    }

    public function isThereSomeOverviews($season): bool
    {
        $episodeOverviewLength = 0;
        if ($season['episodes']) {
            foreach ($season['episodes'] as $episode) {
                $episodeOverviewLength = max($episodeOverviewLength, strlen($episode['overview']));
            }
        }
        return $episodeOverviewLength > 0;
    }

    public function getProviders($watchProviders, $type, $imgConfig, $providers, $country = 'FR', $indexed = true): array
    {
        if ($type) {
            if (key_exists($type, $watchProviders)) {
                foreach ($watchProviders[$type] as &$provider) {
                    $providers = $this->getArr($provider, $providers, $imgConfig, $country, $indexed);
                }
            }
        } else {
            foreach ($providers as &$provider) {
                $providers = $this->getArr($provider, $providers, $imgConfig, $country, $indexed);
            }
        }
        return $providers;
    }

    public function getArr(mixed $provider, array $providers, array $imgConfig, $region = "FR", $indexed = true): array
    {
        $flatrate['logo_path'] = $this->fullUrl('logo', 1, $provider['logo_path'], 'no_provider_logo.png', $imgConfig);
        $flatrate['display_priority'] = $provider['display_priority'];
        $flatrate['provider_id'] = $provider['provider_id'];
        $flatrate['provider_name'] = $provider['provider_name'];
        $flatrate['region'] = $region;
        if ($indexed)
            $providers[$provider['provider_id']] = $flatrate;
        else
            $providers[] = $flatrate;
        return $providers;
    }

    public function fullUrl($type, $size, $filename, $default, $imgConfig): string
    {
        if ($filename && strlen($filename)) {
            return $imgConfig['url'] . $imgConfig[$type . '_sizes'][$size] . $filename;
        } else {
            return "/images/default/" . $default;
        }
    }

    public function getRegionProvider($imgConfig, $size = 1, $language = "fr_FR", $region = "FR"): array
    {
        $list = json_decode($this->TMDBService->getTvWatchProviderList($language, $region), true);
        $list = $list['results'];
        $watchProviderList = [];
        foreach ($list as $provider) {
            $item = [];
            $item['logo_path'] = $this->fullUrl('logo', $size, $provider['logo_path'], 'no_provider_logo.png', $imgConfig);
            $item['provider_name'] = $provider['provider_name'];
            $watchProviderList[$provider['provider_id']] = $item;
        }
//        dump(['watchProviderList' => $watchProviderList]);
        return $watchProviderList;
    }

    #[Route('/episode/substitute/name', name: 'app_episode_substitute_name', methods: ['GET'])]
    public function saveSubstituteName(Request $request): Response
    {
        $data = json_decode($request->query->get('data'), true);

        $episodeViewing = $this->episodeViewingRepository->findOneBy(['id' => $data['id']]);
        $episodeViewing->setSubstituteName($data['substituteName']);
        $this->episodeViewingRepository->save($episodeViewing, true);

        return $this->json([
            'data id' => $data['id'],
            'data substituteName' => $data['substituteName'],
        ]);
    }

    public function getSeasonViewing(SerieViewing $serieViewing, int $seasonNumber): ?SeasonViewing
    {
        $seasonViewing = null;
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber() == $seasonNumber) {
                $seasonViewing = $season;
                break;
            }
        }
        return $seasonViewing;
    }

    public function getEpisodeViewing(SeasonViewing $seasonViewing, int $episodeNumber): ?EpisodeViewing
    {
        $episodeViewing = null;
        foreach ($seasonViewing->getEpisodes() as $episode) {
            if ($episode->getEpisodeNumber() == $episodeNumber) {
                $episodeViewing = $episode;
                break;
            }
        }
        return $episodeViewing;
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
//        dump($tv);
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $serieRepository = $this->serieRepository;
        $imageConfiguration = $this->imageConfiguration;
        $imgConfig = $imageConfiguration->getConfig();

        $serieId = $tv['id'];
        $credits = $tv['credits'];
        $similar = $tv['similar'];
        $images = $tv['images'];

        $keywords = $tv['keywords'];
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $temp = $tv['watch/providers'];
        if ($temp && array_key_exists('FR', $temp['results'])) {
            $watchProviders = $temp['results']['FR'];
            $providersFlatrate = $this->getProviders($watchProviders, 'flatrate', $imgConfig, []); // Providers FR (streaming)
            $watchProviderList = $this->getRegionProvider($imgConfig); // Tous les providers FR
        } else {
            $watchProviders = null;
            $providersFlatrate = [];
            foreach ($temp['results'] as $country => $providers) {
                $providersFlatrate = $this->getProviders($providers, 'flatrate', $imgConfig, $providersFlatrate, $country, false); // Providers (streaming)
            }
            if (!count($providersFlatrate)) {
                $providersFlatrate = null;
                $watchProviderList = null;
            } else {
                $watchProviderList = $this->getRegionProvider($imgConfig, 1, '', ''); // Tous les providers
            }
        }

        $serieViewing = null;
        $whatsNew = null;
        if ($serie == null) {
            $serie = $serieRepository->findOneBy(['serieId' => $serieId]);
        }

        if ($serie) {
            if ($tv['first_air_date'] == null) {
                $tv['upcoming_date_month'] = $serie->getUpcomingDateMonth();
                $tv['upcoming_date_year'] = $serie->getUpcomingDateYear();
            }

            $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);

            if ($serieViewing == null) {
                $serieViewing = $this->createSerieViewing($user, $tv, $serie);
            } else {
                $whatsNew = $this->whatsNew($tv, $serie, $serieViewing);
                $serieViewing = $this->updateSerieViewing($serieViewing, $tv, $serie);
            }
            $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);
            if (!count($serieViewing->getSerieCasts())) {
                $this->updateTvCast($tv, $serieViewing);
            }
            $cast = $this->getCast($serieViewing);
            $credits['cast'] = $cast;

            $seasonsWithAView = [];
            foreach ($tv['seasons'] as $season) {
                $seasonWithAView = $season;
                $seasonWithAView['seasonViewing'] = $serieViewing->getSeasonByNumber($season['season_number']);
                if ($serieViewing->isTimeShifted()) {
                    $airDate = $this->dateService->newDate($season['air_date'], 'Europe/Paris', true);
                    $airDate = $airDate->modify('+1 day');
                    $seasonWithAView['air_date'] = $airDate->format('Y-m-d');
                }
                $seasonsWithAView[] = $seasonWithAView;
            }
            $tv['seasons'] = $seasonsWithAView;
        }

        $ygg = str_replace(' ', '+', $tv['name']);
        $yggOriginal = str_replace(' ', '+', $tv['original_name']);

//        $this->cleanCastTable();
        $tv['seasons'] = array_map(function ($season) use ($tv, $locale) {
            $standing = $this->TMDBService->getTvSeason($tv['id'], $season['season_number'], $locale);
            $seasonTMDB = json_decode($standing, true);
            $season['episodes'] = $seasonTMDB['episodes'];
            return $season;
        }, $tv['seasons']);

        $addThisSeries = !$serieViewing;

        $alert = $serieViewing ? $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]) : null;

        // Breadcrumb
        $breadcrumb = $this->breadcrumb($from, $tv);

//        dump([
//            'tv' => $tv,
//            'watchProviders' => $watchProviders,
//            'providersFlatrate' => $providersFlatrate,
//            'watchProviderList' => $watchProviderList,
//        ]);
        return $this->render('series/show.html.twig', [
            'serie' => $tv,
            'serieId' => $serie?->getId(),
            'addThisSeries' => $addThisSeries,
            'currentYear' => $this->dateService->newDate('now', 'Europe/Paris', true)->format('Y'),
            'credits' => $credits,
            'keywords' => $keywords,
            'missingTranslations' => $missingTranslations,
            'watchProviders' => $watchProviders,
            'providersFlatrate' => $providersFlatrate,
            'watchProviderList' => $watchProviderList,
            'similar' => $similar,
            'serieIds' => $this->mySerieIds($user),
            'images' => $images,
            'locale' => $locale,
            'page' => $page,
            'from' => $from,
            'breadcrumb' => $breadcrumb,
            'backId' => $backId,
            'query' => $query,
            'year' => $year,
            'user' => $user,
            'whatsNew' => $whatsNew,
            'viewedEpisodes' => $serieViewing?->getViewedEpisodes(),
            'isTimeShifted' => $serieViewing?->isTimeShifted(),
            'nextEpisodeToWatch' => $nextEpisodeToWatch ?? null,
            'alert' => $alert,
            'ygg' => $ygg,
            'yggOriginal' => $yggOriginal,
            'imageConfig' => $imgConfig,
        ]);
    }

//    public function cleanCastTable(): void
//    {
//        // Suppression des casts non-utilisés
//        $castRepository = $this->castRepository;
//        $serieCastRepository = $this->serieCastRepository;
//        $serieCasts = $serieCastRepository->findAll();
//        $casts = $castRepository->findAll();
//
//        $serieCastCastIds = array_map(function ($serieCast) {
//            return $serieCast->getCastId();
//        }, $serieCasts);
//
//        $castIds = array_map(function ($cast) {
//            return $cast->getId();
//        }, $casts);
//
//        $castIds = array_diff($castIds, $serieCastCastIds);
//
//        $casts = $castRepository->findBy(['id' => $castIds]);
//
//        foreach ($casts as $cast) {
//            $castRepository->remove($cast);
//        }
//        $castRepository->flush();
//    }

    public function getNextEpisodeToWatch(SerieViewing $serieViewing, $locale): ?array
    {
        $lastNotViewedEpisode = null;
        $seasons = $serieViewing->getSeasons();

        foreach ($seasons as $season) {
            if ($season->getSeasonNumber() && !$season->isSeasonCompleted()) {
                $episodes = $season->getEpisodes();
                foreach ($episodes as $episode) {
                    if (!$episode->getViewedAt()) {
                        $lastNotViewedEpisode = $episode;
                        break 2;
                    }
                }
            }
        }

        if ($lastNotViewedEpisode) {
            $seasonNumber = $lastNotViewedEpisode->getSeason()->getSeasonNumber();
            $episodeNumber = $lastNotViewedEpisode->getEpisodeNumber();
            $airDate = $lastNotViewedEpisode->getAirDate();

            if ($airDate) {
                if ($serieViewing->isTimeShifted()) {
                    $airDate = $airDate->modify('+1 day');
                }
                $now = $this->dateService->newDateImmutable('now', 'Europe/Paris', true);
                $interval = date_diff($now, $airDate);

                return [
                    'episodeNumber' => $episodeNumber,
                    'seasonNumber' => $seasonNumber,
                    'airDate' => $airDate,
                    'interval' => $interval,
                ];
            }

            $serieId = $serieViewing->getSerie()->getSerieId();
            $standing = $this->TMDBService->getTvEpisode($serieId, $seasonNumber, $episodeNumber, $locale);
            $tmdbEpisode = json_decode($standing, true);

            $airDate = null;
            $interval = null;

            if ($tmdbEpisode == null) {
                return [
                    'episodeNumber' => $episodeNumber,
                    'seasonNumber' => $seasonNumber,
                    'airDate' => $airDate,
                    'interval' => $interval,
                ];
            }

            if ($tmdbEpisode['air_date'] == null) {
                return [
                    'episodeNumber' => $tmdbEpisode['episode_number'],
                    'seasonNumber' => $tmdbEpisode['season_number'],
                    'airDate' => $airDate,
                    'interval' => $interval,
                ];
            }

            $airDate = $this->dateService->newDateImmutable($tmdbEpisode['air_date'], 'Europe/Paris', true);
            if ($serieViewing->isTimeShifted()) {
                $airDate = $airDate->modify('+1 day');
            }
            $now = $this->dateService->newDateImmutable('now', 'Europe/Paris', true);
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
//        $tvCast = $tv['credits']['cast'];

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

    public function whatsNew(array $tv, Serie $serie, SerieViewing $serieViewing): array|null
    {
        $whatsNew = ['episode' => 0, 'season' => 0, 'status' => "", 'original_name' => ""];
        $modified = false;
        if ($serie->getNumberOfSeasons() !== $tv['number_of_seasons']) {
            $whatsNew['season'] = $tv['number_of_seasons'] - $serie->getNumberOfSeasons();
            $modified = true;

            $serie->setNumberOfSeasons($tv['number_of_seasons']);
        }
        if ($serie->getNumberOfEpisodes() !== $tv['number_of_episodes']) {
            $whatsNew['episode'] = $tv['number_of_episodes'] - $serie->getNumberOfEpisodes();
            $modified = true;

            $serie->setNumberOfEpisodes($tv['number_of_episodes']);
        }
        if ($serie->getStatus() !== $tv['status']) {
            $whatsNew['status'] = $tv['status'];
            $modified = true;

            $serie->setStatus($tv['status']);
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
            $now = $this->dateService->newDate('now', 'Europe/Paris');
            $serieViewing->setModifiedAt($now);
            $this->serieViewingRepository->save($serieViewing);

            $serie->setUpdatedAt($now);
            $this->serieRepository->save($serie, true);
            return $whatsNew;
        }

        return null;
    }

    #[Route(path: '/viewing', name: 'app_series_viewing')]
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
                            } catch (Exception) {
                                $episode->setAirDate(null);
                            }
                        }
                    }

                    $episode->setNetworkId($networkId ?: null);
                    $episode->setNetworkType($networkType != "" ? $networkType : null);
                    $episode->setDeviceType($deviceType != "" ? $deviceType : null);
                    if ($liveWatch) {
                        if ($episode->getAirDate()) {
                            $episode->setViewedAt($episode->getAirDate());
                        } else {
                            if (!$episodeTmdb) {
                                $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);
                            }
                            if ($episodeTmdb) {
                                $episode->setViewedAt($this->dateService->newDateImmutable($episodeTmdb['air_date'], 'Europe/Paris'));
                            }
                        }
                    } else {
                        $episode->setViewedAt($this->dateService->newDateImmutable('now', 'Europe/Paris'));
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
                    $this->dateService->newDateImmutable($episodeTmdb['air_date'], 'Europe/Paris');
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
            $serieViewing->setModifiedAt($this->dateService->newDate('now', 'Europe/Paris'));
        } else {
            $createdAt = $this->getSerieViewingCreatedAt($serieViewing, $request);
            if ($createdAt) {
                $modifiedAt = $this->dateService->getNow();
                $serieViewing->setModifiedAt($modifiedAt->setTimestamp($createdAt->getTimestamp()));
            }
        }
        $this->serieViewingRepository->save($serieViewing, true);
        $this->setViewedEpisodeCount($serieViewing);
        /* ----end---- entity based viewing ----end---- */

        $blocks = [];
        $globalIndex = 1;
        $viewed = 0;
        $s = [];
        foreach ($serieViewing->getSeasons() as $seasonViewing) {
            if ($seasonViewing->getSeasonNumber()) { // 21/12/2022 : plus d'épisodes spéciaux
                $standing = $this->TMDBService->getTvSeason($serie->getSerieId(), $seasonViewing->getSeasonNumber(), $locale);
                $seasonTMDB = json_decode($standing, true);
                $s['episodes'] = $seasonTMDB['episodes'];
                $s['seasonViewing'] = $seasonViewing;
                $blocks[] = [
                    'season' => $seasonViewing->getSeasonNumber(),
                    'episode_count' => $seasonViewing->getEpisodeCount(),
                    'view' => $this->render('blocks/series/_season_viewing.html.twig', [
                        'season' => $s,
                        'shift' => $serieViewing->isTimeShifted(),
                        'locale' => $locale,
                        'globalIndex' => $globalIndex,
                    ])
                ];
                $viewed += $seasonViewing->getViewedEpisodeCount();
                $globalIndex += $seasonViewing->getEpisodeCount();
            }
        }

        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);
        // On met à jour les champs "nextEpisodeToAir" et "nextEpisodeToWatch" de la série
        if ($serieViewing->isSerieCompleted()) {
            $serieViewing->setNextEpisodeToAir(null);
            $serieViewing->setNextEpisodeToWatch(null);
            if ($alert) {
                $this->alertRepository->remove($alert);
            }
        } else {
            $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);

            $tv = json_decode($this->TMDBService->getTv($serie->getSerieId(), $locale), true);
            $this->setNextEpisode($tv, $serieViewing);
            if ($alert) {
                $this->updateAlert($alert, $serieViewing);
            }
        }
        $blockNextEpisodeToWatch = $this->render('blocks/series/_next_episode_to_watch.html.twig', [
            'nextEpisodeToWatch' => $nextEpisodeToWatch ?? null,
            'alert' => $alert,
        ]);

        return $this->json([
            'blocks' => $blocks,
            'blockNextEpisodeToWatch' => $blockNextEpisodeToWatch,
            'viewedEpisodes' => $viewed,
            'episodeText' => $translator->trans($viewed > 1 ? "viewed episodes" : "viewed episode"),
            'seasonCompleted' => $serieViewing->getSeasonByNumber($season)->isSeasonCompleted(),
        ]);
    }

//    public function networks2Array($networks): array
//    {
//        $networksArray = [];
//        foreach ($networks as $network) {
//            $netWorkArray['id'] = $network->getNetworkId();
//            $networkArray['name'] = $network->getName();
//            $networkArray['logoPath'] = $network->getLogoPath();
//            $networkArray['originCountry'] = $network->getOriginCountry();
//            $networkArray['networkId'] = $network->getNetworkId();
//            $networksArray[] = $networkArray;
//        }
//        return $networksArray;
//    }

    public function getSerieViewingCreatedAt($serieViewing, $request): DateTimeImmutable|null
    {
        $createdAt = $serieViewing->getCreatedAt();

        if ($createdAt) {
            return $createdAt;
        }

        $serie = $serieViewing->getSerie();

        $createdAt = $serie->getFirstDateAir();
        if ($createdAt) {
            $serieViewing->setCreatedAt($createdAt);
            return $createdAt;
        }

        $standing = $this->TMDBService->getTvEpisode($serie->getSerieId(), 1, 1, $request->getLocale());
        $firstEpisode = json_decode($standing, true);
        if ($firstEpisode && $firstEpisode['air_date']) {
            $createdAt = $this->dateService->newDateImmutable($firstEpisode['air_date'], 'Europe/Paris');
            $serieViewing->setCreatedAt($createdAt);
            return $createdAt;
        }

        $standing = $this->TMDBService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);
        if ($tv && $tv['first_air_date']) {
            $createdAt = $this->dateService->newDateImmutable($tv['first_air_date'], 'Europe/Paris');
            $serieViewing->setCreatedAt($createdAt);
        }
        return $createdAt;
    }

    public function viewingCompleted(SerieViewing $serieViewing): bool
    {
        $seasonsCompleted = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber()) {
                if ($season->getEpisodeCount()) { //une saison peut être annoncée/ajoutée avec zéro épisode
                    $completed = $season->getViewedEpisodeCount() == $season->getEpisodeCount();
                    if ($completed && !$season->isSeasonCompleted()) {
                        $season->setSeasonCompleted(true);
                        $this->seasonViewingRepository->save($season, true);
                    }
                    if ($completed) $seasonsCompleted++;
                }
            }
        }
        if ($serieViewing->getNumberOfSeasons() == 0) {
            $serieViewing->setNumberOfSeasons(count($serieViewing->getSeasons()));
            $this->serieViewingRepository->save($serieViewing, true);
        }
        if ($serieViewing->getNumberOfSeasons() == $seasonsCompleted) {
            $serieViewing->setSerieCompleted(true);
            $this->serieViewingRepository->save($serieViewing, true);
            return true;
        }
        return false;
    }

    public function setViewedEpisodeCount($serieViewing): int
    {
        $viewedEpisodeCount = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            if ($season->getSeasonNumber()) { // 21/12/2022 : finito les épisodes spéciaux
                $viewedEpisodeCount += $season->getViewedEpisodeCount();
            }
        }
        $serieViewing->setViewedEpisodes($viewedEpisodeCount);
        $this->serieViewingRepository->save($serieViewing, true);

        return $viewedEpisodeCount;
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

    #[Route('/upcoming/date', name: 'app_series_upcoming_date', methods: ['GET'])]
    public function serieUpcomingDate(Request $request): Response
    {
        $id = $request->query->getInt('id');
        $month = $request->query->get('month', null);
        $year = $request->query->get('year', null);

        $serie = $this->serieRepository->find($id);
        $serie->setUpcomingDateMonth($month);
        $serie->setUpcomingDateYear($year);
        $this->serieRepository->save($serie, true);

        return $this->json(['id' => $id, 'month' => $month, 'year' => $year]);
    }

    #[Route('/alert/{serieId}/{tmdb}/{isActivated}', name: 'app_series_alert', methods: ['GET'])]
    public function serieAlert(Request $request, int $serieId, string $tmdb, bool $isActivated): Response
    {
        if ($tmdb == 'tmdb') {
            $serie = $this->serieRepository->findOneBy(['serieId' => $serieId]);
        } else {
            $serie = $this->serieRepository->findOneBy(['id' => $serieId]);
        }
        $serieViewing = $this->serieViewingRepository->findOneBy(['serie' => $serie, 'user' => $this->getUser()]);
//      dump(['serie' => $serie, 'serieViewing' => $serieViewing, 'isActivated' => $isActivated]);

        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);
        $success = true;

        if ($alert === null) {
            $locale = $request->getLocale();
            $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);

            if ($nextEpisodeToWatch) {
                /** @var DateTimeImmutable $airDate */
                $airDate = $nextEpisodeToWatch['airDate'];
                $airDate = $airDate->setTime(9, 0); // Netflix : 9h01, Apple TV+ : 9h00, Disney+ : 9h00, Prime Video : 9h00
                $message = sprintf("%s : S%02dE%02d\n", $serie->getName(), $nextEpisodeToWatch['seasonNumber'], $nextEpisodeToWatch['episodeNumber']);
                $alert = new Alert($this->getUser(), $serieViewing->getId(), $airDate, $message, $this->dateService);
                $this->alertRepository->save($alert, true);
                $alertMessage = $this->translator->trans("Alert created and activated");
            } else {
                $success = false;
                $alertMessage = $this->translator->trans("No upcoming episodes");
            }
        } else {
            $alert->setActivated($isActivated);
            $this->alertRepository->save($alert, true);
            if ($alert->isActivated()) {
                $alertMessage = $this->translator->trans("Alert activated");
            } else {
                $alertMessage = $this->translator->trans("Alert deactivated");
            }
        }
        return $this->json([
            'success' => $success,
            'alertMessage' => $alertMessage,
        ]);
    }

    #[Route('/alert-provider/{id}/{providerId}', name: 'app_series_alert_provider', methods: ['GET'])]
    public function serieProvider(Request $request, int $id, int $providerId): Response
    {
        $show = $request->query->get('show', false);
        if ($show)
            $serie = $this->serieRepository->find($id);
        else
            $serie = $this->serieRepository->findOneBy(['serieId' => $id]);
        $serieViewing = $this->serieViewingRepository->findOneBy(['serie' => $serie, 'user' => $this->getUser()]);
//        dump(['show' => $show, 'id' => $id, 'serie' => $serie, 'serieViewing' => $serieViewing, 'providerId' => $providerId]);
        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);
        $alert->setProviderId($providerId);
        $this->alertRepository->save($alert, true);

        $region = $request->query->get('region', "FR");
        $languages = ['FR' => 'fr-FR', 'US' => 'en-US', 'UK' => 'en-GB', 'DE' => 'de-DE', 'ES' => 'es-ES', 'IT' => 'it-IT', 'JP' => 'ja-JP', 'CA' => 'en-CA', 'AU' => 'en-AU', 'NZ' => 'en-NZ', 'IN' => 'en-IN', 'MX' => 'es-MX', 'BR' => 'pt-BR'];
        if (key_exists($region, $languages)) {
            $language = $languages[$region];
        } else { // json de 7400+ lignes
            $region = '';
            $language = '';
        }

        $imgConfig = $this->imageConfiguration->getConfig();
        $list = $this->getRegionProvider($imgConfig, 1, $language, $region);
        $block = '<img src="' . $list[$providerId]['logo_path'] . '" alt="' . $list[$providerId]['provider_name'] . '" title="' . $list[$providerId]['provider_name'] . '">';

        return $this->json(['success' => true, 'block' => $block]);
    }

    public function updateAlert(Alert $alert, SerieViewing $serieViewing): void
    {
        $next = $serieViewing->getNextEpisodeToWatch();
        $date = $serieViewing->getNextEpisodeToWatch()?->getAirDate();
        if ($date) {
            $message = sprintf("%s : S%02dE%02d\n",
                $serieViewing->getSerie()->getName(),
                $serieViewing->getNextEpisodeToWatch()->getSeason()->getSeasonNumber(),
                $serieViewing->getNextEpisodeToWatch()->getEpisodeNumber());
            $date = $date->setTime(9, 0);
            $alert->setMessage($message);
            $alert->setDate($date);
            $alert->setActivated(true);
            $this->alertRepository->save($alert, true);
        } else {
            $this->alertRepository->remove($alert, true);
            $this->addFlash('info', $this->translator->trans($next ? 'No date yet for the upcoming episode' : 'No upcoming episodes for the moment'));
        }
    }

    #[Route('/episode/vote/{id}/{vote}', name: 'app_episode_vote', methods: ['GET'])]
    public function episodeVote(EpisodeViewing $episodeViewing, int $vote): Response
    {
        $episodeViewing->setVote($vote);
        $this->episodeViewingRepository->save($episodeViewing, true);
        return $this->json(['vote' => $vote, 'episodeNumber' => $episodeViewing->getEpisodeNumber()]);
    }

    #[Route('/episode/view/{id}/{view}', name: 'app_episode_view', methods: ['GET'])]
    public function episodeView(Request $request, EpisodeViewing $episodeViewing, int $view): Response
    {
        if ($view == 0) {
            $date = $this->dateService->newDateImmutable('now', 'Europe/Paris');
            $episodeViewing->setViewedAt($date);
            $view = 1;
        } else {
            $episodeViewing->setViewedAt(null);
            $view = 0;
        }
        $this->episodeViewingRepository->save($episodeViewing, true);

        $serieViewing = $episodeViewing->getSeason()->getSerieViewing();
        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);

        $modifiedAt = $this->dateService->newDate('now', 'Europe/Paris');
        $serieViewing->setModifiedAt($modifiedAt);

        $this->setViewedEpisodeCount($serieViewing);
        $seasonCompleted = $this->viewingCompleted($serieViewing);
        $viewedEpisodeCount = $episodeViewing->getSeason()->getViewedEpisodeCount();

        // On met à jour les champs "nextEpisodeToAir" et "nextEpisodeToWatch" de la série
        if ($serieViewing->isSerieCompleted()) {
            $serieViewing->setNextEpisodeToAir(null);
            $serieViewing->setNextEpisodeToWatch(null);
            if ($alert) {
                $this->alertRepository->remove($alert);
            }
        } else {
            $tv = json_decode($this->TMDBService->getTv($serieViewing->getSerie()->getSerieId(), $request->getLocale()), true);
            $this->setNextEpisode($tv, $serieViewing);
            if ($alert) {
                $this->updateAlert($alert, $serieViewing);
            }
        }

        $this->serieViewingRepository->save($serieViewing, true);

        return $this->json([
            'episodeViewed' => $view,
            'seasonCompleted' => $seasonCompleted,
            'viewedEpisodeCount' => $viewedEpisodeCount,
        ]);
    }

    #[Route('/episode/view/network/{id}/{networkId}', name: 'app_episode_view_network', methods: ['GET'])]
    public function episodeViewNetwork(EpisodeViewing $episodeViewing, int $networkId): Response
    {
        if ($networkId == -1) {
            $episodeViewing->setNetworkId(null);
            $episodeViewing->setNetworkType('other');
        } else {
            $episodeViewing->setNetworkId($networkId);
            $episodeViewing->setNetworkType('flatrate');
        }
        $this->episodeViewingRepository->save($episodeViewing, true);

        return $this->json([
            'networkId' => $networkId,
            'result' => 'ok'
        ]);
    }

    #[Route('/episode/view/device/{id}/{device}', name: 'app_episode_view_device', methods: ['GET'])]
    public function episodeViewDevice(EpisodeViewing $episodeViewing, string $device): Response
    {
        $episodeViewing->setDeviceType($device);
        $this->episodeViewingRepository->save($episodeViewing, true);

        return $this->json([
            'device' => $device,
            'result' => 'ok'
        ]);
    }

    public function mySerieIds(User $user): array
    {
        return array_map(function ($mySerieId) {
            return $mySerieId['serieId'];
        }, $this->serieRepository->findMySerieIds($user->getId()));
    }
}

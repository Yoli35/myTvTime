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
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    const SEARCH = 'search';

    public function __construct(private readonly AlertRepository          $alertRepository,
                                private readonly CastRepository           $castRepository,
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

    #[Route('/', name: 'app_serie_index', methods: ['GET'])]
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
            $now = $this->newDateImmutable('now', 'Europe/Paris');

            foreach ($series as &$serie) {
                $serie = $this->isSerieAiringSoon($serie, $now);
            }
        }

        return $this->render('serie/index.html.twig', [
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
            'from' => self::MY_SERIES,
            'imageConfig' => $imageConfig,
        ]);
    }

    public function newDate($dateString, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date->setTime(0, 0);

        return $date;
    }

    public function newDateImmutable($dateString, $timeZone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if (!$allDay) $date->setTime(0, 0);

        return $date;
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
            $serie['viewing'] = $this->getSerieViews($result, $serieViewings);
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

    #[Route('/today', name: 'app_serie_today', methods: ['GET'])]
    public function today(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

        $day = $request->query->getInt('d');
        $week = $request->query->getInt('w');
        $month = $request->query->getInt('m');

        $datetime = $day . ' day ' . $week . ' week ' . $month . ' month';
        $date = $this->newDateImmutable($datetime, 'Europe/Paris');
        $date = $date->setTime(0, 0);
        $now = $this->newDateImmutable('now', 'Europe/Paris');
        $now = $now->setTime(0, 0);

        $diff = date_diff($now, $date);
        $delta = $diff->days;

        /** @var Serie[] $todayAirings */
        $todayAirings = $this->todayAiringSeries($date);
//        dump($todayAirings);
        $backdrop = $this->getTodayAiringBackdrop($todayAirings);
        $images = $this->getNothingImages();

        return $this->render('serie/today.html.twig', [
            'todayAirings' => $todayAirings,
            'date' => $date,
            'backdrop' => $backdrop,
            'images' => $images,
            'prev' => $delta * ($diff->invert ? -1 : 1),
            'next' => $delta * ($diff->invert ? -1 : 1),
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

    #[Route('/to-start', name: 'app_serie_to_start', methods: ['GET'])]
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
        $serieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'viewedEpisodes' => 0], [$sort => $order], $perPage, ($page - 1) * $perPage);

        $locale = $request->getLocale();
        $imageConfig = $this->imageConfiguration->getConfig();
        $seriesToBeStarted = $this->seriesToBeToArray($user, $serieViewings, $imageConfig, $locale);

        $totalResults = $this->serieViewingRepository->count(['user' => $user, 'viewedEpisodes' => 0]);

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
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'from' => self::MY_SERIES_TO_START,
            'imageConfig' => $imageConfig,
        ]);
    }

    #[Route('/to-end', name: 'app_serie_to_end', methods: ['GET'])]
    public function seriesToEnd(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
//        $this->logService->log($request, $this->getUser());

        $page = $request->query->getInt('p', 1);
        $perPage = 10;
        $sort = 'createdAt';
        $order = 'ASC';

        /** @var User $user */
        $user = $this->getUser();
        $serieViewings = $this->serieViewingRepository->getSeriesToEnd($user, $perPage, $page);

        $locale = $request->getLocale();
        $imageConfig = $this->imageConfiguration->getConfig();
        $seriesToBeEnded = $this->seriesToBeToArray($user, $serieViewings, $imageConfig, $locale);

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
            'posters' => $this->getPosters(),
            'posterPath' => '/images/series/posters/',
            'from' => self::MY_SERIES_TO_END,
            'imageConfig' => $imageConfig,
        ]);
    }

    function getPosters(): array|false
    {
        $posterFiles = scandir($this->getParameter('kernel.project_dir') . '/public/images/series/posters');
        return array_slice($posterFiles, 3);
    }

    public function seriesToBeToArray($user, $serieViewings, $imageConfig, $locale): array
    {
        $serieViewingIds = array_map(function ($serieViewing) {
            return $serieViewing->getId();
        }, $serieViewings);
        $ids = $this->serieViewingRepository->getSerieIds($serieViewingIds);
        $ids = array_map(function ($item) {
            return $item['id'];
        }, $ids);
        $results = $this->serieRepository->findBy(['id' => $ids]);
        // trier results par id selon ids
        $results = array_map(function ($id) use ($results) {
            foreach ($results as $result) {
                if ($result->getId() === $id) {
                    return $result;
                }
            }
            return null;
        }, $ids);
//        dump(['test' => $ids, 'results' => $results, 'serieViewings' => $serieViewings]);

//        $results = array_map(function ($serieViewing) {
//            return $serieViewing->getSerie();
//        }, $serieViewings);
//        $ids = array_map(function ($result) {
//            return $result->getId();
//        }, $results);

        $favorites = $this->favoriteRepository->findBy(['type' => 'serie', 'userId' => $user->getId(), 'mediaId' => $ids]);
        $networks = $this->serieRepository->networks($ids);

        /** @var Serie $result */
        $seriesToBe = array_map(function ($result) use ($serieViewings, $imageConfig, $locale, $favorites, $networks) {
            $serie = $this->serie2array($result, $locale);
            $serie['viewing'] = $this->getSerieViews($result, $serieViewings);
            $serie['favorite'] = $this->isFavorite($serie, $favorites);
            $serie['networks'] = $this->getNetworks($serie, $networks);

            $this->savePoster($serie['posterPath'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
            return $serie;
        }, $results);

        $now = new DateTime();
        $now->setTime(0, 0);
        foreach ($seriesToBe as &$serie) {
            $serie = $this->isSerieAiringSoon($serie, $now);
        }

        return $seriesToBe;
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

    #[Route('/search/{page}', name: 'app_serie_search', defaults: ['page' => 1], methods: ['GET', 'POST'])]
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
        /** @var User $user */
        $user = $this->getUser();
        $page = $request->query->getInt('p', 1);
        $locale = $request->getLocale();

        $standing = $this->TMDBService->getSeries(self::POPULAR, $page, $locale);
        $series = json_decode($standing, true);
        $imageConfig = $this->imageConfiguration->getConfig();

        foreach ($series['results'] as $serie) {
            $this->savePoster($serie['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
        }

        return $this->render('serie/popular.html.twig', [
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($user) : [],
            'pages' => [
                'total_results' => $series['total_results'],
                'page' => $page,
                'per_page' => 20,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'from' => self::POPULAR,
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
            try {
                $serieViewing->setCreatedAt(new DateTimeImmutable($tv['first_air_date']));
            } catch (Exception) {
                $serieViewing->setCreatedAt(new DateTimeImmutable());
            }
            $modified = true;
        }
        if ($serieViewing->getModifiedAt() == null) {
            try {
                $serieViewing->setModifiedAt(new DateTime($tv['first_air_date']));
            } catch (Exception) {
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
                    if ($season->getEpisodeCount() === $s['episode_count']) {
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
                    } else {
                        if ($season->getEpisodeCount() < $s['episode_count']) {
                            for ($i = $season->getEpisodeCount() + 1; $i <= $s['episode_count']; $i++) {
                                $this->addNewEpisode($tv, $season, $i);
                            }
                        } else {
                            for ($i = $s['episode_count'] + 1; $i <= $season->getEpisodeCount(); $i++) {
                                $episode = $season->getEpisodeByNumber($i);
                                if ($episode !== null) {
                                    $season->removeEpisodeViewing($episode);
                                    $this->episodeViewingRepository->remove($episode, true);
                                }
                            }
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
//        $this->logService->log($request, $this->getUser());

        $tmdbService = $this->TMDBService;

        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']);
        if ($standing == "") {
            return $this->render('serie/error.html.twig', [
                'serie' => $serie,
            ]);
        }
        $tv = json_decode($standing, true);

        return $this->getSerie($request, $tv, $page, $from, $serie->getId(), $serie, $query, $year);
    }

    #[Route('/tmdb/{id}', name: 'app_serie_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id): Response
    {
//        $this->logService->log($request, $this->getUser());
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
                            $airDate = new DateTime($dateString);
                            $airDate->modify('+1 day');
                            $tmdbEpisode['air_date'] = $airDate->format('Y-m-d');
                        } catch (Exception) {

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
        if ($serie['userSerieViewing']) {
            $seasonViewing = $this->getSeasonViewing($serie['userSerieViewing'], $seasonNumber);
            $episodeViewings = $seasonViewing?->getEpisodes();
        } else {
            $seasonViewing = null;
            $episodeViewings = null;
        }
        $episodes = array_map(function ($episode) use ($episodeViewings) {
            $episode['viewing'] = null;
            if ($episodeViewings) {
                foreach ($episodeViewings as $episodeViewing) {
                    if ($episodeViewing->getEpisodeNumber() == $episode['episode_number']) {
                        $episode['viewing'] = $episodeViewing;
                        break;
                    }
                }
            }
            return $episode;
        }, $episodes);
//        dump(['season' => $season, 'seasonViewing' => $seasonViewing, 'episodes' => $episodes]);

//        dump([
//            '$episodeViewings[0]' => $episodeViewings[0],
//            '$episodeViewings[1]' => $episodeViewings[1],
//            '$episodeViewings[2]' => $episodeViewings[2],
//            '$episodeViewings[3]' => $episodeViewings[3],
//            '$episodeViewings[4]' => $episodeViewings[4],
//            '$episodeViewings[5]' => $episodeViewings[5],
//            '$episodeViewings[6]' => $episodeViewings[6],
//            ]);

        return $this->render('serie/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'seasonViewing' => $seasonViewing,
//            'episodeViewings' => $episodeViewings,
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
            }
        }
        return $seasonViewing;
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
                $whatsNew = $this->whatsNew($tv, $serie);

                /** @var SerieViewing $serieViewing */
                $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
                if ($serieViewing == null) {
                    $serieViewing = $this->createSerieViewing($user, $tv, $serie);
                } else {
                    $serieViewing = $this->updateSerieViewing($serieViewing, $tv, $serie);
                }
                $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);
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
                            $airDate = new DateTimeImmutable($season['air_date']);
                            $airDate = $airDate->modify('+1 day');
                            $seasonWithAView['air_date'] = $airDate->format('Y-m-d');
                        } catch (Exception) {

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
        $tv['seasons'] = array_map(function ($season) use ($tv, $locale) {
            $standing = $this->TMDBService->getTvSeason($tv['id'], $season['season_number'], $locale);
            $seasonTMDB = json_decode($standing, true);
            $season['episodes'] = $seasonTMDB['episodes'];
            return $season;
        }, $tv['seasons']);
//        dump($tv);

        $alert = $serieViewing ? $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]) : null;

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
            'alert' => $alert,
            'ygg' => $ygg,
            'yggOriginal' => $yggOriginal,
            'imageConfig' => $imageConfiguration->getConfig(),
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
                $airDate = new DateTimeImmutable($tmdbEpisode['air_date']);
            } catch (Exception) {
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
            $now = new DateTimeImmutable('now');
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

    #[Route('/alert/{serieId}/{tmdb}/{isActivated}', name: 'app_serie_alert', methods: ['GET'])]
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
                $airDate = $nextEpisodeToWatch['airDate'];
                $message = sprintf("%s : S%02dE%02d\n", $serie->getName(), $nextEpisodeToWatch['seasonNumber'], $nextEpisodeToWatch['episodeNumber']);
                $alert = new Alert($this->getUser(), $serieViewing->getId(), $airDate, $message);
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

    public function whatsNew(array $tv, Serie $serie): array|null
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
            $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]);
            $serieViewing->setModifiedAt(new DateTime());
            $this->serieViewingRepository->save($serieViewing, true);

            $serie->setUpdatedAt(new DateTime());
            $this->serieRepository->save($serie, true);
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
                                try {
                                    $episode->setViewedAt(new DateTimeImmutable($episodeTmdb['air_date']));
                                } catch (Exception) {
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
                    } catch (Exception) {
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
                    'view' => $this->render('blocks/serie/_season_viewing.html.twig', [
                        'season' => $s,
                        'globalIndex' => $globalIndex,
                    ])
                ];
                $viewed += $seasonViewing->getViewedEpisodeCount();
                $globalIndex += $seasonViewing->getEpisodeCount();
            }
        }

        $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);
        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);
        $blockNextEpisodeToWatch = $this->render('blocks/serie/_next_episode_to_watch.html.twig', [
            'nextEpisodeToWatch' => $nextEpisodeToWatch,
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
            $createdAt = $this->newDateImmutable($firstEpisode['air_date'], 'Europe/Paris');
            $serieViewing->setCreatedAt($createdAt);
            return $createdAt;
        }

        $standing = $this->TMDBService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);
        if ($tv && $tv['first_air_date']) {
            $createdAt = $this->newDateImmutable($tv['first_air_date'], 'Europe/Paris');
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

    #[Route('/episode/vote/{id}/{vote}', name: 'app_episode_vote', methods: ['GET'])]
    public function episodeVote(EpisodeViewing $episodeViewing, int $vote): Response
    {
        $episodeViewing->setVote($vote);
        $this->episodeViewingRepository->save($episodeViewing, true);
        return $this->json(['vote' => $vote]);
    }

    #[Route('/episode/view/{id}/{view}', name: 'app_episode_view', methods: ['GET'])]
    public function episodeView(EpisodeViewing $episodeViewing, int $view): Response
    {
        if ($view == 0) {
            $date = $this->newDateImmutable('now', 'Europe/Paris');
            $episodeViewing->setViewedAt($date);
            $view = 1;
        } else {
            $episodeViewing->setViewedAt(null);
            $view = 0;
        }
        $this->episodeViewingRepository->save($episodeViewing, true);

        $serieViewing = $episodeViewing->getSeason()->getSerieViewing();
        $modifiedAt = $this->newDate('now', 'Europe/Paris');

        $serieViewing->setModifiedAt($modifiedAt);
        $this->serieViewingRepository->save($serieViewing, true);

        $seasonCompleted = $this->viewingCompleted($serieViewing);
        $viewedEpisodeCount = $this->setViewedEpisodeCount($serieViewing);

        return $this->json([
            'episodeViewed' => $view,
            'seasonCompleted' => $seasonCompleted,
            'viewedEpisodeCount' => $viewedEpisodeCount
        ]);
    }

    public function mySerieIds(User $user): array
    {
        return array_map(function ($mySerieId) {
            return $mySerieId['serieId'];
        }, $this->serieRepository->findMySerieIds($user->getId()));
    }
}

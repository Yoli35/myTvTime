<?php

namespace App\Controller;

use App\Breadcrumb\BreadcrumbBuilder;
use App\Entity\Alert;
use App\Entity\Cast;
use App\Entity\EpisodeViewing;
use App\Entity\Favorite;
use App\Entity\Networks;
use App\Entity\SeasonViewing;
use App\Entity\Serie;
use App\Entity\SerieAlternateOverview;
use App\Entity\SerieBackdrop;
use App\Entity\SerieCast;
use App\Entity\SerieLocalizedName;
use App\Entity\SeriePoster;
use App\Entity\SerieViewing;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\SerieSearchType;
use App\Form\TvFilterType;
use App\Repository\AlertRepository;
use App\Repository\CastRepository;
use App\Repository\EpisodeViewingRepository;
use App\Repository\FavoriteRepository;
use App\Repository\NetworksRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieBackdropRepository;
use App\Repository\SerieCastRepository;
use App\Repository\SerieLocalizedNameRepository;
use App\Repository\SeriePosterRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Service\DateService;
use App\Service\DeeplTranslator;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use DatePeriod;
use DeepL\DeepLException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
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
    const SERIES_FROM_COUNTRY = 'series_from_country';
    const EPISODES_OF_THE_DAY = 'today';
    const EPISODES_OF_THE_WEEK = 'week';
    const UPCOMING_EPISODES = 'upcoming_episodes';
    const UPCOMING_SERIES = 'upcoming_series';
    const POPULAR_SERIES = 'popular';
    const SERIES_FILTER = 'filter';
    const TOP_RATED = 'top_rated';
    const SEARCH_SERIES = 'search';
    const MY_EVENTS = 'my_events';

    public array $messages = [];

    public function __construct(
//        private readonly BreadcrumbBuilder            $breadcrumbBuilder,
        private readonly AlertRepository              $alertRepository,
        private readonly CastRepository               $castRepository,
        private readonly DateService                  $dateService,
        private readonly DeeplTranslator              $deeplTranslator,
        private readonly EpisodeViewingRepository     $episodeViewingRepository,
        private readonly FavoriteRepository           $favoriteRepository,
        private readonly ImageConfiguration           $imageConfiguration,
//        private readonly NetworksRepository           $networksRepository,
        private readonly SeasonViewingRepository      $seasonViewingRepository,
        private readonly SerieCastRepository          $serieCastRepository,
        private readonly SerieBackdropRepository      $serieBackdropRepository,
        private readonly SerieLocalizedNameRepository $serieLocalizedNameRepository,
        private readonly SeriePosterRepository        $seriePosterRepository,
        private readonly SerieRepository              $serieRepository,
        private readonly SerieViewingRepository       $serieViewingRepository,
        private readonly SettingsRepository           $settingsRepository,
        private readonly TMDBService                  $TMDBService,
        private readonly TranslatorInterface          $translator,
//        private readonly UserTvPreferenceRepository   $userTvPreferenceRepository,
    )
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
            $now = $this->dateService->newDateImmutable('now', $user->getTimezone());

            foreach ($series as &$serie) {
                $serie = $this->isSerieAiringSoon($serie, $now);
            }
        }
        $history = $this->getHistory($user, $request->getLocale());

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'numbers' => $serieRepository->numbers($user->getId())[0],
            'seriesList' => $list,
            'countries' => $this->getCountries(),
            'history' => $history,
            'historyPerPage' => 40,
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

    #[Route('/today', name: 'app_series_today', methods: ['GET'])]
    public function today(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();
        $imgConfig = $this->imageConfiguration->getConfig();

        $day = $request->query->getInt('d');
        $week = $request->query->getInt('w');
        $month = $request->query->getInt('m');

        $datetime = $day . ' day ' . $week . ' week ' . $month . ' month';
        $date = $this->dateService->newDateImmutable($datetime, $user->getTimezone());
        $date = $date->setTime(0, 0);
        $now = $this->dateService->newDateImmutable('now', $user->getTimezone());
        $now = $now->setTime(0, 0);

        $diff = date_diff($now, $date);
        $delta = $diff->days;

        /** @var Serie[] $todayAirings */
        $todayAirings = $this->todayAiringSeriesV2($date);
        $seriesToWatch = [];

        if (count($todayAirings)) {
            foreach ($todayAirings as $todayAiring) {
                $this->savePoster($todayAiring['seriePosterPath'], $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
            }
//        dump($todayAirings);
            $backdrop = $this->getTodayAiringBackdrop($todayAirings);
            $images = [];
        } else {
            $backdrop = null;
            $images = $this->getNothingImages();
            $seriesToWatch = $this->serieViewingRepository->getSeriesToWatch($user->getId(), $user->getPreferredLanguage() ?? $request->getLocale(), 20, 1);
            $seriesToWatch = array_map(function ($series) use ($imgConfig) {
                if ($series['time_shifted']) {
                    $airDate = $series['air_date'];
                    $date = $this->dateService->newDate($series['air_date'], 'Europe/Paris', true);
                    $series['air_date'] = $date->modify('+1 day')->format('Y-m-d');
//                    dump([
//                        'series' => $series['name'],
//                        'air date' => $airDate,
//                        'new date' => $date->format('Y-m-d')
//                    ]);
                }
                $this->savePoster($series['poster_path'], $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
                $series['poster_path'] = $this->fullUrl("poster", 3, $series['poster_path'], "no_poster_dark.png", $imgConfig);
                return $series;
            }, $seriesToWatch);
//            dump(['series to watch' => $seriesToWatch]);
//            dump(['user Tv Preferences (findBy)' => $this->userTvPreferenceRepository->findBy(['user' => $user->getId()], ['vitality' => 'DESC'])]);
//            dump(['user Tv Preferences (queryBuilder)' => $this->userTvPreferenceRepository->getUserTvPreferences($user)]);
//            dump(['user Tv Preferences (SQL)' => $this->userTvPreferenceRepository->getUserTvPreferencesSQL($user->getId())]);
        }
        $breadcrumb = $this->breadcrumb(self::EPISODES_OF_THE_DAY);
        $breadcrumb[0]['separator'] = '●';
        $breadcrumb[] = ['name' => $this->translator->trans("Episodes of the week"), 'url' => $this->generateUrl("app_series_this_week")];

        $bc = new BreadcrumbBuilder($this->translator);
        $bc->rootBreadcrumb('Home', $this->generateUrl('app_home'), '●')
            ->addBreadcrumb('My series airing today', $this->generateUrl('app_series_today'));
//        dump($bc);

        return $this->render('series/day.html.twig', [
            'todayAirings' => $todayAirings,
            'seriesToWatch' => $seriesToWatch,
            'date' => $date,
            'backdrop' => $backdrop,
            'images' => $images,
            'prev' => $delta * ($diff->invert ? -1 : 1),
            'next' => $delta * ($diff->invert ? -1 : 1),
            'breadcrumb' => $breadcrumb,
            'bc' => $bc->getBreadcrumbs(),
            'from' => self::EPISODES_OF_THE_DAY,
            'imageConfig' => $imgConfig,
        ]);
    }

    #[Route('/this-week', name: 'app_series_this_week', methods: ['GET'])]
    public function episodesOfTheWeek(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $imgConfig = $this->imageConfiguration->getConfig();

        $today = $this->dateService->newDateImmutable('now', $user->getTimezone());
        $week = $request->query->getInt('w', $today->format('W'));
        $year = $request->query->getInt('y', $today->format('o')); // Year the ISO week number (W) belongs to

        $firstDay = $today->setISODate($year, $week, 1);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $firstDay;
            $firstDay = $firstDay->modify('+1 day');
        }

        $day_of_the_week = $today->format('N');

        $previousMonday = $today->setISODate($year, $week, 1)->sub($this->dateService->interval('P1W'));
        $nextMonday = $today->setISODate($year, $week, 1)->add($this->dateService->interval('P1W'));
        $previousWeek = intval($previousMonday->format('W'));
        $nextWeek = intval($nextMonday->format('W'));
        $previousYear = intval($previousMonday->format('o'));
        $nextYear = intval($nextMonday->format('o'));

        $episodesOfTheWeek = [];
        $episodesCount = 0;
        for ($i = 1; $i <= 7; $i++) {
            $day = $days[$i - 1];
            $episodesOfTheDay = $this->todayAiringSeriesV2($day);
            foreach ($episodesOfTheDay as $episode) {
                $episodesCount += count($episode['episodeNumbers']);
                $this->savePoster($episode['seriePosterPath'], $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
            }
            $episodesOfTheWeek[] = [
                'day' => $day,
                'episodes' => $episodesOfTheDay,
                'today_offset' => $i - $day_of_the_week,
            ];
        }
        $seriesToWatch = $this->serieViewingRepository->getSeriesToWatch($user->getId(), $user->getPreferredLanguage() ?? $request->getLocale(), 40, 1);
        $seriesToWatch = array_map(function ($series) use ($imgConfig) {
            if ($series['time_shifted']) {
                $date = $this->dateService->newDate($series['air_date'], 'Europe/Paris', true);
                $series['air_date'] = $date->modify('+1 day')->format('Y-m-d');
            }
            $this->savePoster($series['poster_path'], $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
            $series['poster_path'] = $this->fullUrl("poster", 3, $series['poster_path'], "no_poster_dark.png", $imgConfig);
            return $series;
        }, $seriesToWatch);

        $breadcrumb = $this->breadcrumb(self::EPISODES_OF_THE_WEEK);
        $breadcrumb[0]['separator'] = '●';
        $breadcrumb[] = ['name' => $this->translator->trans("My series airing today"), 'url' => $this->generateUrl("app_series_today")];
        $imageConfig = $this->imageConfiguration->getConfig();

        return $this->render('series/week.html.twig', [
            'date' => $today,
            'week' => ['week_number' => $week, 'start' => $days[0], 'end' => $days[6], 'previous' => $previousWeek, 'next' => $nextWeek, 'nextYear' => $nextYear, 'previousYear' => $previousYear],
            'episodesCount' => $episodesCount,
            'episodesOfTheWeek' => $episodesOfTheWeek,
            'seriesToWatch' => $seriesToWatch,
            'dayNames' => $this->dateService->getDayNames(100),
            'breadcrumb' => $breadcrumb,
            'from' => self::EPISODES_OF_THE_WEEK,
            'imageConfig' => $imageConfig,
            'images' => $this->getNothingImages(),
        ]);
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
        $results = $this->serieViewingRepository->getSeriesToStartV2($user, $request->getLocale(), $perPage, $page);

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
        $results = $this->serieViewingRepository->getSeriesToEndV2($user->getId(), $request->getLocale(), $perPage, $page);

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

        $results = array_map(function ($result) use ($request, $user, $imageConfig) {
            // on rajoute les networks
            $tv = json_decode($this->TMDBService->getTv($result['tmdb_id'], $request->getLocale()), true);
            $result['networks'] = $tv ? $tv['networks'] : [];

            // Date et épisode. Ex : Demain /  S01E01
            $now = $this->dateService->newDate('now', $user->getTimezone(), true);
            $date = $this->dateService->newDate($result['air_date'], $user->getTimezone());
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
                $result['air_date'] = $this->dateService->formatDate($date, $user->getTimezone(), $request->getLocale());
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

    #[Route('/country/{countryCode}', name: 'app_series_from_country', methods: ['GET'])]
    public function seriesFromCountry(Request $request, string $countryCode): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        $imageConfig = $this->imageConfiguration->getConfig();

        $series = $this->serieRepository->getSeriesFromCountry($user->getId(), $countryCode, 0, 50);
        $series = array_map(function ($serie) use ($imageConfig) {
            $serie['poster_path'] = $this->fullUrl("poster", 3, $serie['poster_path'], "no_poster_dark.png", $imageConfig);
            return $serie;
        }, $series);
        $seriesWithFirstDateAir = array_filter($series, function ($serie) {
            return $serie['first_date_air'];
        });
        $seriesWithoutFirstDateAir = array_filter($series, function ($serie) {
            return !$serie['first_date_air'];
        });
        $series = array_merge($seriesWithoutFirstDateAir, $seriesWithFirstDateAir);
//        dump([
//            'user' => $user,
//            'seriesWithFirstDateAir' => $seriesWithFirstDateAir,
//            'seriesWithoutFirstDateAir' => $seriesWithoutFirstDateAir,
//        ]);
        if ($countryCode == 'all')
            $countryName = $this->translator->trans('All countries');
        else
            $countryName = Countries::getName($countryCode, $request->getLocale());
        $count = count($series);
        $totalResults = $this->serieRepository->seriesFromCountryCount($user->getId(), $countryCode);

        return $this->render('series/from_country.html.twig', [
            'series' => $series,
            'countryCode' => $countryCode,
            'countryName' => $countryName,
            'countries' => $this->getCountries(),
            'breadcrumb' => [
                [
                    'name' => $this->translator->trans('My series'),
                    'url' => $this->generateUrl('app_series_index')
                ],
                [
                    'name' => $this->translator->trans('Series') . " - " . $countryName,
                    'url' => $this->generateUrl('app_series_from_country', ['countryCode' => $countryCode])
                ],
                [
                    'name' => $count . ' / ' . $totalResults . ' ' . $this->translator->trans($count > 1 ? 'series' : 'serie'),
                ],
            ],
            'from' => self::SERIES_FROM_COUNTRY,
        ]);
    }

    #[Route('/search/{page}', name: 'app_series_search', defaults: ['page' => 1], methods: ['GET', 'POST'])]
    public function search(Request $request, int $page): Response
    {
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
            'serieIds' => $this->mySerieIds($user),
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

        $standing = $this->TMDBService->getSeries(self::TOP_RATED, $page, $locale, $user->getTimezone());
        $series = json_decode($standing, true);
        $imageConfig = $this->imageConfiguration->getConfig();

        foreach ($series['results'] as $serie) {
            $this->savePoster($serie['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
        }

        $breadcrumb = $this->breadcrumb(self::POPULAR_SERIES);

        return $this->render('series/popular.html.twig', [
            'series' => $series['results'],
            'serieIds' => $this->mySerieIds($user),
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

    #[Route('/filter', name: 'app_series_filter', methods: ['GET', 'POST'])]
    public function filter(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $tvFilterSettings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'tv_filter']);
        if ($tvFilterSettings)
            $data = $tvFilterSettings->getData();
        else
            $data = $this->createTvFilterSettings($user);

        $watchProviders = $this->getWatchProviders($user->getPreferredLanguage() ?? 'en-US', $user->getCountry() ?? 'US');

        $data['watchProviderSelect'] = $watchProviders['watchProviderSelect'];
        $data['genreSelect'] = $this->getTvGenres($user->getPreferredLanguage() ?? 'en-US');
        $data['watchRegionSelect'] = $this->getAvailableRegions();

        $form = $this->createForm(TvFilterType::class, null, [
            'data' => $data,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
        }

        $filters = $this->getTvFilters($data);
        $filterString = "&page=" . $data['page'] . "&sort_by=" . $data['sort_by'] . "." . $data['order_by'];
        foreach ($filters as $key => $value) {
            $filterString .= "&$key=$value";
        }
//        dump([
//            '$filters' => $filters,
//            '$filterString' => $filterString,
//        ]);
        $standing = $this->TMDBService->getFilterTv($filterString);
        $series = json_decode($standing, true);
        $totalResults = $series['total_results'];
        $totalPages = $series['total_pages'];
        $imageConfig = $this->imageConfiguration->getConfig();

        $arr = $this->serieViewingRepository->getUserSeriesProgressAndLocalizedName($user->getId(), array_column($series['results'], 'id'), $user->getPreferredLanguage() ?? $request->getLocale());
        $userSeriesProgress = array_combine(array_column($arr, 'id'), array_column($arr, 'progress'));
        $userSeriesLocalizedName = array_combine(array_column($arr, 'id'), array_column($arr, 'localized_name'));
//        dump([
//            'results' => $series['results'], // 'id', 'name', 'poster_path
//            'ids' => array_column($series['results'], 'id'),
//            'arr' => $arr, // 'id', 'progress', 'localized_name
//            'userSeriesProgress' => $userSeriesProgress,
//            'userSeriesLocalizedName' => $userSeriesLocalizedName,
//        ]);
        $series = array_map(function ($serie) use ($imageConfig, $userSeriesProgress, $userSeriesLocalizedName) {
            $this->savePoster($serie['poster_path'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);
            $serie['poster_path'] = $this->fullUrl("poster", 3, $serie['poster_path'], "no_poster_dark.png", $imageConfig);
            $serie['has_progress'] = isset($userSeriesProgress[$serie['id']]);
            $serie['progress'] = $userSeriesProgress[$serie['id']] ?? 0;
            $serie['localized_name'] = $userSeriesLocalizedName[$serie['id']] ?? null;
            return $serie;
        }, $series['results']);
//        dump($series);

        $breadcrumb = $this->breadcrumb(self::SERIES_FILTER);

        return $this->render('series/filter.html.twig', [
            'series' => $series,
            'serieIds' => $this->mySerieIds($user),
            'form' => $form->createView(),
            'logos' => $watchProviders['watchProviderLogos'],
            'total_results' => $totalResults,
            'total_pages' => $totalPages,
            'breadcrumb' => $breadcrumb,
            'from' => self::SERIES_FILTER,
            'user' => $user,
        ]);
    }

    #[Route('/show/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Request $request, Serie $serie, BreadcrumbBuilder $bc): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $tmdbService = $this->TMDBService;

        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        if ($from === self::SERIES_FROM_COUNTRY) {
            $query = $request->query->get('c', "");
        } else {
            $query = $request->query->get('query', "");
        }
        $year = $request->query->get('year', "");

        $tv = json_decode($tmdbService->getTv($serie->getSerieId(), $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']), true);
        if (!$tv) {
            return $this->render('series/error.html.twig', [
                'serie' => $serie,
                'serieViewing' => $this->serieViewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]),
                'imageConfig' => $this->imageConfiguration->getConfig(),
            ]);
        }
        return $this->displaySeriesPage($request, $tv, $page, $from, $serie->getId(), $serie, $query, $year);
    }

    #[Route('/tmdb/{id}', name: 'app_series_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id): Response
    {
        if ($this->getUser()) {
            $serie = $this->serieRepository->findOneBy(['serieId' => $id]);
            if ($serie) {
                return $this->redirectToRoute('app_series_show', ['id' => $serie->getId()]);
            }
        }
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $tv = json_decode($this->TMDBService->getTv($id, $request->getLocale(), ['credits', 'keywords', 'watch/providers', 'similar', 'images', 'videos']), true);
        if (!$tv) {
            $serie = $this->serieRepository->findOneBy(['serieId' => $id]);
            return $this->render('series/error.html.twig', [
                'serie' => $serie,
                'serieViewing' => $this->serieViewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]),
                'imageConfig' => $this->imageConfiguration->getConfig(),
            ]);
        }

        return $this->displaySeriesPage($request, $tv, $page, $from, $id, null, $query, $year);
    }

    #[Route('/show/{id}/season/{seasonNumber}', name: 'app_series_tmdb_season', methods: ['GET'])]
    public function season(Request $request, $id, $seasonNumber): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $imgConfig = $this->imageConfiguration->getConfig();

        $from = $request->query->get('from');
        $page = $request->query->get('p');
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');

        // user preferred language-country, country
        $locale = $request->getLocale();
        $countries = ['fr' => 'FR', 'en' => 'US', 'es' => 'ES', 'de' => 'DE'];
        $country = $user?->getCountry() ?? $countries[$locale];
        $language = ($user?->getPreferredLanguage() ?? $locale) . '-' . $country;

        // Cookie pour le layout
        $seasonsCookie = $this->seasonsCookie();

        // La série (db ou the movie db) et sa bannière
        $serie = $this->serie($id, $request->getLocale());
        $serie['backdropPath'] = $this->fullUrl('backdrop', 3, $serie['backdropPath'], 'no_banner_dark.png', $imgConfig);

        // La saison (db ou the movie db) et son affiche (poster)
        $standing = $this->TMDBService->getTvSeason($id, $seasonNumber, $language, ['credits', 'watch/providers']);
        $season = json_decode($standing, true);

        // Si les données de la saison ne sont pas disponibles dans la langue de l'utilisateur, on les prend en anglais et on les traduit (deepl)
        $localization = $this->seasonLocalizedOverview($id, $season, $seasonNumber, $locale);

        $credits = $season['credits'];
        if (!key_exists('cast', $credits)) {
            $credits['cast'] = [];
        }
        $season['poster_path'] = $this->fullUrl('poster', 5, $season['poster_path'], 'no_poster.png', $imgConfig);

        // Les épisodes (the movie db), l'ajustement de la date (J+1 ?) et leurs affiches (still)
        $episodes = [];
        $isShifted = $serie['userSerieViewing'] && $serie['userSerieViewing']->isTimeShifted();
        foreach ($season['episodes'] as $episode) {
            if ($isShifted) {
                if ($episode['air_date']) {
                    $airDate = $this->dateService->newDate($episode['air_date'], $user->getTimezone())?->modify('+1 day')->format('Y-m-d');
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
//            dump($cast);
            $credits['cast'][$key]['profile_path'] = $this->fullUrl('profile', 2, $cast['profile_path'], 'no_profile.png', $imgConfig);
        }
        if (key_exists('crew', $credits)) {
            foreach ($credits['crew'] as $key => $crew) {
                $credits['crew'][$key]['profile_path'] = $this->fullUrl('profile', 2, $crew['profile_path'], 'no_profile.png', $imgConfig);
            }
        }
        // Les détails liés à l'utilisateur/spectateur (série, saison, épisodes)
        if ($serie['userSerieViewing']) {
            // les infos de la saison
            $seasonViewing = $this->getSeasonViewing($serie['userSerieViewing'], $seasonNumber);
            // les infos des épisodes
            $episodes = array_map(function ($episode) use ($seasonViewing, $isShifted) {
                $episodeViewing = $this->getEpisodeViewing($seasonViewing, $episode['episode_number']);
                $episode['viewing'] = $episodeViewing;
                return $episode;
            }, $episodes);

            $episodesVotes = $this->seasonEpisodeVotes($episodes);
            list($episodes, $modifications) = $this->seasonCheckEpisodeDates($user, $episodes, $isShifted);
        } else {
            $seasonViewing = null;
        }

        $watchProviders = $this->seasonWatchProviders($id, $season, $language, $country);

        // Breadcrumb
        $breadcrumb = $this->breadcrumb($from, $serie, $season, null, $from == self::SERIES_FROM_COUNTRY ? $request->query->get('c', "FR") : null);

//        dump([
//            'serie' => $serie,
//            'env' => $_ENV['APP_ENV'],
//            'season' => $season,
//            'modifications' => $modifications,
//            'watchProviders' => $watchProviders,
//        ]);

        return $this->render('series/season.html.twig', [
            'serie' => $serie,
            'season' => $season,
            'seasonViewing' => $seasonViewing,
            'episodes' => $episodes,
            'episodesVotes' => $episodesVotes ?? null,
            'credits' => $credits,
            'watchProviders' => $watchProviders['seasonWatchProviders'],
            'allWatchProviders' => $watchProviders['allWatchProviders'],
            'seasonsCookie' => $seasonsCookie,
            'modifications' => $modifications ?? null,
            'localization' => $localization,
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

    #[Route('/filter/save/settings', name: 'app_series_filter_save_settings', methods: ['POST'])]
    public function saveTvFilterSettings(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'tv_filter']);
        $settings->setData($data);
        $this->settingsRepository->save($settings, true);

        return $this->json(['success' => true]);
    }

    #[Route('/filter/load/settings', name: 'app_series_filter_load_settings', methods: ['POST'])]
    public function loadTvFilterSettings(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $settings = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'tv_filter']);

        return $this->json($settings->getData());
    }

    public function getCountries(): array
    {
        $results = $this->serieRepository->getCountries();
        $arr = [];
        foreach ($results as $result) {
            $countries = json_decode($result['origin_country'], true);
            foreach ($countries as $country) {
                $arr[] = $country;
            }
        }
        $arr = array_unique($arr);
        $arr = array_values($arr);
//        dump($arr);
        $countries = [];
        foreach ($arr as $country) {
            if (strlen($country) == 2)
                $countries[$country] = Countries::getName($country);
        }
        asort($countries, SORT_FLAG_CASE | SORT_STRING);
        return array_merge(["all" => $this->translator->trans("All countries")], $countries);
    }

    public function getHistory($user, $locale, $page = 1, $limit = 40): array
    {
        return array_map(function ($h) {
            $h['poster_path'] = $this->fullUrl('poster', 2, $h['serie_poster_path'], 'no_poster.png', $this->imageConfiguration->getConfig());
            return $h;
        }, $this->episodeViewingRepository->episodeUserHistory($user->getId(), $user->getPreferredLanguage() ?? $locale, $page, $limit));
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

            $this->savePoster($serie['posterPath'], $imageConfig['url'] . $imageConfig['poster_sizes'][3]);

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
        $serie['upcomingDateYear'] = $result->getUpcomingDateYear();
        $serie['upcomingDateMonth'] = $result->getUpcomingDateMonth();

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

    public function todayAiringSeriesV2(DateTimeImmutable $date): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $today = $date->format('Y-m-d');
        $yesterday = $date->sub(new DateInterval('P1D'))->format('Y-m-d');

        $episodesOfTheDay = $this->serieViewingRepository->getEpisodesOfTheDay($user->getId(), $today, $yesterday, 1, 20);
        /*        dump([
                    'today' => $today,
                    'yesterday' => $yesterday,
                    'episodes of the day' => $episodesOfTheDay
                ]);*/
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
            } else {
                sort($ep['episodeNumbers']);
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

    public function getPosters(): array|false
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
        $series = $this->serieRepository->findBy(['id' => array_map(function ($result) {
            return $result['serie_id'];
        }, $results)]);
        $seriesToBe = array_map(function ($result) use ($series, $serieViewings, $serieViewingIds, $imageConfig, $locale) {

            $result = $this->updateSerieDB($result, $series, $locale);

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

    public function updateSerieDB($result, $series, $locale): array
    {
        // result :
        //    "id" => 741
        //    "viewed_episodes" => 0
        //    "number_of_episodes" => 7
        //    "number_of_seasons" => 1
        //    "serie_completed" => 0
        //    "time_shifted" => 0
        //    "modified_at" => "2023-07-14 12:00:05"
        //    "created_at" => "2023-07-14 12:00:05"
        //    "alert_id" => null
        //    "serie_id" => 671
        //    "name" => "A Murder at the End of the World"
        // *  "poster_path" => null
        // *  "first_date_air" => "2023-08-29 00:00:00"
        //    "original_name" => "A Murder at the End of the World"
        // *  "overview" => ""
        // *  "backdrop_path" => "/eFt73bTmYZZirCYnDh946eBnBey.jpg"
        //    "tmdb_id" => 134095
        // *  "serie_status" => "In Production"
        //    "serie_created_at" => "2023-07-14 12:00:05"
        //    "serie_updated_at" => "2023-07-14 12:00:05"
        //    "favorite" => 0
        /** @var User $user */
        $user = $this->getUser();

        $serieDB = $this->getSerieFromDB($result['serie_id'], $series);

        $missingPosterPath = false;
        $missingBackdropPath = false;
        $missingFirstDateAir = false;
        $missingOverview = false;
        $somethingMissing = false;
        $somethingToSave = false;
        $serieTMDB = null;

        if (!$serieDB->getPosterPath()) {
            $missingPosterPath = true;
        }
        if (!$serieDB->getBackdropPath()) {
            $missingBackdropPath = true;
        }
        if (!$serieDB->getFirstDateAir()) {
            $missingFirstDateAir = true;
        }
        if ($serieDB->getOverview() === "") {
            $missingOverview = true;
        }
        if ($missingBackdropPath || $missingFirstDateAir || $missingOverview || $missingPosterPath) { // Si quelque chose manque, vérifier si la série a été mise à jour sur TMDB
            $serieTMDB = json_decode($this->TMDBService->getTv($serieDB->getSerieId(), $locale), true);
            // vérifier le statut ici devrait suffire
            if ($result['serie_status'] !== $serieTMDB['status']) {
                $serieDB->setStatus($serieTMDB['status']);
                $result['serie_status'] = $serieTMDB['status'];
                $somethingToSave = true;
                $this->addFlash('success', 'Status updated for ' . $serieDB->getName());
            }
            $somethingMissing = true;
        }
        if ($somethingMissing && $serieTMDB) {

            $posterPath = $serieTMDB['poster_path'];
            if ($posterPath != $serieDB->getPosterPath()) {
                $serieDB->setPosterPath($posterPath);
                $result['poster_path'] = $posterPath;
                $somethingToSave = true;
                $this->addFlash('success', 'Poster path updated for ' . $serieDB->getName());
            }
            $backdropPath = $serieTMDB['backdrop_path'];
            if ($backdropPath != $serieDB->getBackdropPath()) {
                $serieDB->setBackdropPath($backdropPath);
                $result['backdrop_path'] = $backdropPath;
                $somethingToSave = true;
                $this->addFlash('success', 'Backdrop path updated for ' . $serieDB->getName());
            }
            $firstDateAir = $serieTMDB['first_air_date'];
            if ($firstDateAir !== "") {
                $firstDateAirTMDB = $this->dateService->newDateImmutable($firstDateAir, $user->getTimezone(), true);
//                $firstDateAirDB = $serieDB->getFirstDateAir()->setTimezone(new DateTimeZone($user->getTimezone()))->setTime(0, 0);
                $firstDateAirDB = $this->dateService->newDateImmutable($serieDB->getFirstDateAir()->format('Y-m-d'), $user->getTimezone(), true)->setTime(0, 0);
                if ($firstDateAirTMDB != $firstDateAirDB) {
                    $result['first_date_air'] = $firstDateAir;
                    $serieDB->setFirstDateAir($firstDateAirTMDB);
                    $somethingToSave = true;
                    $this->addFlash('success', 'First date air updated for ' . $serieDB->getName());
                }
            }
            $overview = $serieTMDB['overview'];
            if ($overview != $serieDB->getOverview()) {
                $serieDB->setOverview($overview);
                $result['overview'] = $overview;
                $somethingToSave = true;
                $this->addFlash('success', 'Overview updated for ' . $serieDB->getName());
            }

            if ($somethingToSave) {
                $this->serieRepository->save($serieDB, true);
            }
        }
        return $result;
    }

    public function serie2arrayV2($result, $locale): array
    {
        $tv = json_decode($this->TMDBService->getTv($result['tmdb_id'], $locale), true);

        $serie['id'] = $result['serie_id']; //getId();
        $serie['name'] = $result['name']; //getName();
        $serie['localized_name'] = $result['localized_name']; //getLocalizedName()->getName();
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
        $serie['upcomingDateYear'] = $result['upcoming_date_year']; //getUpcomingDateYear();
        $serie['upcomingDateMonth'] = $result['upcoming_date_month']; //getUpcomingDateMonth();

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

    public function getSerieFromDB(int $id, array $series): Serie|null
    {
        foreach ($series as $serie) {
            if ($serie->getId() === $id) {
                return $serie;
            }
        }
        return null;
    }

    public function savePoster($posterPath, $posterUrl): void
    {
        if (!$posterPath) return;
        $root = $this->getParameter('kernel.project_dir');
        if (!file_exists($root . "/public/images/series/posters" . $posterPath)) {
            $this->saveImageFromUrl(
                $posterUrl . $posterPath,
                $root . "/public/images/series/posters" . $posterPath
            );
        }
    }

    public function getTvFilters(array $data): array
    {
        $data['switch_sort_by'] = false;
        $data['switch_order_by'] = false;

        $switches = array_filter($data, function ($key) {
            return str_contains($key, 'switch_');
        }, ARRAY_FILTER_USE_KEY);
        $fields = array_filter($data, function ($key) {
            return !str_contains($key, 'switch_');
        }, ARRAY_FILTER_USE_KEY);
//        dump([
//            '$data' => $data,
//            '$switches' => $switches,
//            '$fields' => $fields,
//        ]);
        $filters = [];
        foreach ($fields as $key => $value) {
            $switchKey = 'switch_' . $key;
            if (array_key_exists($switchKey, $switches)) {
                $switch = $switches['switch_' . $key];
                if ($switch) {
                    if ($key == 'with_genres') {
                        $value = implode(',', $value);
                    }
                    if ($key == 'first_air_date_gte' || $key == 'first_air_date_lte') {
                        if (!$value) $value = $this->dateService->getNow('UTC', true);
                        $value = $value->format('Y-m-d');
                    }
                    if ($key == 'include_adult' || $key == 'include_null_first_air_date') {
                        $value = $value ? 'true' : 'false';
                    }
                    // Replace '_gte' and '_lte' by '.gte' and '.lte' in keys
                    $key = str_replace('_gte', '.gte', $key);
                    $key = str_replace('_lte', '.lte', $key);
                    $filters[$key] = $value;
                }
            }
        }
        return $filters;
    }

    public function createTvFilterSettings(User $user): array
    {
        $data = [
            "sort_by" => "primary_release_date",
            "order_by" => "desc",

            "switch_with_status" => false,
            "with_status" => null,

            "switch_watch_region" => true,
            "watch_region" => "FR",
            "switch_with_watch_monetization_types" => true,
            "with_watch_monetization_types" => "flatrate",
            "switch_with_watch_providers" => true,
            "with_watch_providers" => 8,

            "switch_with_origin_country" => true,
            "with_origin_country" => "FR",
            "switch_with_original_language" => true,
            "with_original_language" => "fr",

            "switch_with_genres" => false,
            "with_genres" => [],

            "switch_first_air_date_year" => false,
            "first_air_date_year" => null,
            "switch_first_air_date_gte" => false,
            "first_air_date_gte" => null,
            "switch_first_air_date_lte" => false,
            "first_air_date_lte" => null,
            "switch_include_null_first_air_date" => false,
            "include_null_first_air_date" => false,

            "switch_language" => true,
            "language" => 'fr',
            "switch_timezone" => true,
            "timezone" => 'Europe/Paris',

            "switch_vote_average_gte" => false,
            "vote_average_gte" => null,
            "switch_vote_average_lte" => false,
            "vote_average_lte" => null,
            "switch_vote_count_gte" => false,
            "vote_count_gte" => null,
            "switch_vote_count_lte" => false,
            "vote_count_lte" => null,

            "switch_with_runtime_gte" => false,
            "with_runtime_gte" => null,
            "switch_with_runtime_lte" => false,
            "with_runtime_lte" => null,

            "switch_screened_theatrically" => false,
            "screened_theatrically" => false,

            "switch_include_adult" => true,
            "include_adult" => false,

            "switch_page" => true,
            "page" => 1,
        ];
        $settings = new Settings();
        $settings->setName('tv_filter');
        $settings->setUser($user);
        $settings->setData($data);
        $this->settingsRepository->save($settings, true);

        return $data;
    }

    public function getWatchProviders($language, $watchRegion): array
    {
        $providers = json_decode($this->TMDBService->getTvWatchProviderList($language, $watchRegion), true);
        $providers = $providers['results'];
        $watchProviders = [];
        foreach ($providers as $provider) {
            $watchProviders[$provider['provider_name']] = $provider['provider_id'];
        }
        $watchProviderLogos = [];
        foreach ($providers as $provider) {
            $watchProviderLogos[$provider['provider_id']] = $this->fullUrl('logo', 2, $provider['logo_path'], '', $this->imageConfiguration->getConfig());
        }
        ksort($watchProviders);
        return ['watchProviderSelect' => $watchProviders, 'watchProviderLogos' => $watchProviderLogos];
    }

    public function getTvGenres($language): array
    {
        $genres = json_decode($this->TMDBService->getTvGenreList($language), true);
        $genres = $genres['genres'];
        $tvGenres = [];
        foreach ($genres as $genre) {
            $tvGenres[$genre['name']] = $genre['id'];
        }
        ksort($tvGenres);
        return $tvGenres;
    }

    public function getAvailableRegions(): array
    {
        $regions = json_decode($this->TMDBService->availableRegions(), true);
        $regions = $regions['results'];
        $availableRegions = [];
        foreach ($regions as $region) {
            $availableRegions[$region['english_name']] = $region['iso_3166_1'];
        }
        ksort($availableRegions);
        return $availableRegions;
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
        /** @var User $user */
        $user = $this->getUser();

        foreach ($tv['seasons'] as $s) {
            if ($s['season_number']) { // 21/12/2022 : plus d'épisodes spéciaux
                $airDate = $s['air_date'] ? $this->dateService->newDateImmutable($s['air_date'], $user->getTimezone(), true) : null;
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
            $now = $this->dateService->newDate('now', $user->getTimezone(), true);
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

    public function updateSerieViewing(SerieViewing $serieViewing, array $tv, bool $verbose = false, bool $flashes = false): SerieViewing
    {
        /** @var User $user */
        $user = $this->getUser();
        $timezone = $user ? $user->getTimezone() : 'Europe/Paris';

        $modified = false;
        if ($serieViewing->getNumberOfSeasons() != $tv['number_of_seasons']) {
            if ($serieViewing->getNumberOfSeasons() > $tv['number_of_seasons']) {
                $haveToSaveSerieViewing = false;
                if ($serieViewing->getNextEpisodeToWatch()) {
                    $serieViewing->setNextEpisodeToWatch(null);
                    $haveToSaveSerieViewing = true;
                }
                if ($serieViewing->getNextEpisodeToAir()) {
                    $serieViewing->setNextEpisodeToAir(null);
                    $haveToSaveSerieViewing = true;
                }
                if ($haveToSaveSerieViewing) {
                    $this->serieViewingRepository->save($serieViewing, true);
                }
                for ($i = $tv['number_of_seasons'] + 1; $i <= $serieViewing->getNumberOfSeasons(); $i++) {
                    $seasonViewing = $this->getSeasonViewing($serieViewing, $i);
                    $this->seasonViewingRepository->remove($seasonViewing, true);
                }
            }
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
            $serieViewing->setCreatedAt($this->dateService->newDateImmutable($tv['first_air_date'], $timezone));
            $modified = true;
        }
        if ($serieViewing->getModifiedAt() == null) {
            $serieViewing->setModifiedAt($this->dateService->newDate($tv['first_air_date'], $timezone));
            $modified = true;
        }
        if ($modified) {
            $this->serieViewingRepository->save($serieViewing, true);
        }

        foreach ($tv['seasons'] as $s) {
            if ($s['season_number'] == 0) continue; // 21/12/2022 : plus d'épisodes spéciaux

            $season = $serieViewing->getSeasonByNumber($s['season_number']);
            if ($season === null) {
                $airDate = $s['air_date'] ? $this->dateService->newDateImmutable($s['air_date'], $timezone, true) : null;
                $season = new SeasonViewing($airDate, $s['season_number'], $s['episode_count'], false);
                $this->seasonViewingRepository->save($season, true);
                $serieViewing->addSeason($season);
                for ($i = 1; $i <= $s['episode_count']; $i++) {
                    $this->addNewEpisode($tv, $season, $i);
                }
                if ($flashes) {
                    $seasonNumber = sprintf('S%02d', $s['season_number']);
                    $this->addFlash('success', $this->translator->trans('Serie "serieName", season seasonNumber (episodeCount ep.) added.',
                        ['serieName' => $serieViewing->getSerie()->getName(), 'seasonNumber' => $seasonNumber, 'episodeCount' => $s['episode_count']]));
                }
            } else {

                if ($season->getEpisodeCount() == $s['episode_count']) continue;

                if ($season->getEpisodeCount() < $s['episode_count']) {
                    for ($i = $season->getEpisodeCount() + 1; $i <= $s['episode_count']; $i++) {
                        $this->addNewEpisode($tv, $season, $i);
                        $episodeNumber = sprintf('S%02dE%02d', $season->getSeasonNumber(), $i);
                        if ($flashes) $this->addFlash('success', $this->translator->trans('Serie "serieName", episode episodeNumber added.',
                            ['serieName' => $serieViewing->getSerie()->getName(), 'episodeNumber' => $episodeNumber]));
                    }
                } else {
                    $serieViewing->setNextEpisodeToAir(null);
                    $serieViewing->setNextEpisodeToWatch(null);
                    for ($i = $s['episode_count'] + 1; $i <= $season->getEpisodeCount(); $i++) {
                        $episode = $season->getEpisodeByNumber($i);
                        if ($episode !== null) {
                            $season->removeEpisodeViewing($episode);
                            $this->episodeViewingRepository->remove($episode, true);
                        }
                        $episodeNumber = sprintf('S%02dE%02d', $season->getSeasonNumber(), $i);
                        if ($flashes) $this->addFlash('success', $this->translator->trans('Serie "serieName", episode episodeNumber removed.',
                            ['serieName' => $serieViewing->getSerie()->getName(), 'episodeNumber' => $episodeNumber]));
                    }
                }
                $season->setEpisodeCount($s['episode_count']);
            }
            $this->seasonViewingRepository->save($season, true);

            if (!$season->isSeasonCompleted()) {
                $episodes = $season->getEpisodes();
//                dump($episodes);
                foreach ($episodes as $episode) {
                    if ($episode->getAirDate() === null) {
                        $standing = $this->TMDBService->getTvEpisode($tv['id'], $s['season_number'], $episode->getEpisodeNumber(), 'fr');
                        $tmdbEpisode = json_decode($standing, true);
                        if ($tmdbEpisode && key_exists('air_date', $tmdbEpisode) && $tmdbEpisode['air_date']) {
                            $episode->setAirDate($this->dateService->newDateImmutable($tmdbEpisode['air_date'], $timezone));
                            $this->episodeViewingRepository->save($episode, true);
                            if (!$verbose) $this->addFlash('success', 'Date mise à jour : ' . $tmdbEpisode['air_date']);
                        }
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
        /** @var User $user */
        $user = $this->getUser();
        $timezone = $user ? $user->getTimezone() : 'Europe/Paris';

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
        if ($nextEpisodeCheck) {
            $serieViewing->setNextEpisodeCheckDate($this->dateService->newDate('now', $timezone));
        }
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

    public function displaySeriesPage(Request $request, array $tv, int $page, string $from, $backId, Serie|null $serie, $query = "", $year = ""): Response
    {
//        dump($tv);
        /** @var User $user */
        $user = $this->getUser();
        $locale = $request->getLocale();
        $serieRepository = $this->serieRepository;
        $imgConfig = $this->imageConfiguration->getConfig();

        $serieId = $tv['id'];
        $credits = $tv['credits'];
        $similar = $tv['similar'];
        $images = $tv['images'];

        $keywords = $tv['keywords'];
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $temp = $tv['watch/providers'];
//        if ($user) {
        $country = $user?->getCountry() ?? 'FR';
        $language = $user?->getPreferredLanguage() ?? 'fr';
//        } else {
//            $country = 'FR';
//            $language = 'fr';
//        }
        if ($temp && array_key_exists($country, $temp['results'])) {
            $watchProviders = $temp['results'][$country];
            $providersFlatrate = $this->getProviders($watchProviders, 'flatrate', $imgConfig, []); // Providers FR (streaming)
            $watchProviderList = $this->getRegionProvider($imgConfig, 1, $language . '_' . $country, $country); // Tous les providers FR
        } else {
            $watchProviders = null;
            $providersFlatrate = [];
            foreach ($temp['results'] as $country => $providers) {
                $providersFlatrate = $this->getProviders($providers, 'flatrate', $imgConfig, $providersFlatrate, $country, false); // Providers (streaming)
            }
            if (!count($providersFlatrate)) {
                $providersFlatrate = null;
            }
            $watchProviderList = $this->getRegionProvider($imgConfig, 1, '', ''); // Tous les providers
        }
//        dump(['temp' => $temp, 'providersFlatrate' => $providersFlatrate, 'watchProviderList' => $watchProviderList]);

        $serieViewing = null;
        $whatsNew = null;
        if ($serie == null) {
            $serie = $serieRepository->findOneBy(['serieId' => $serieId]);
        }

        if ($serie) {
//            dump(['tv' => $tv, 'serie' => $serie]);
            if ($serie->getSerieLocalizedName() && $tv['name'] !== $serie->getSerieLocalizedName()->getName()) {
                $tv['localized_name'] = $serie->getSerieLocalizedName()->getName();
            }
            $tv['alternate_overviews'] = array_filter($serie->getSeriesAlternateOverviews()->toArray(), function ($overview) use ($locale) {
                return $overview->getLocale() == $locale;
            });
            if ($tv['first_air_date'] == null || $tv['first_air_date'] == "") {
                $tv['first_air_date'] = $serie->getFirstDateAir()->format('Y-m-d');
            }
        } else {
            $tv['alternate_overviews'] = [];
        }

        if ($user && $serie) {
            if ($tv['first_air_date'] == null) {
                $tv['upcoming_date_month'] = $serie->getUpcomingDateMonth();
                $tv['upcoming_date_year'] = $serie->getUpcomingDateYear();
            }
            $tv['seriePosters'] = $serie->getSeriePosters();
            $tv['serieBackdrops'] = $serie->getSerieBackdrops();
            $tv['directLink'] = [];
            $dls = $serie->getDirectLink();
            $urls = [];
            if ($dls) {
                $dls = explode(',', $dls);
//                dump($serie->getDirectLink(), $dls);
                if ($dls && count($dls)) {
                    $index = 0;
                    foreach ($dls as $dl) {
                        if ($dl && strlen($dl) > 0) {
                            $tv['directLink'][$index]['url'] = $dl;
                            $urls[] = $dl;

                            // https://www.youtube.com/watch?v=xpiuV0Xj8zk&list=PLxaYND3fuRFPPEfhIfs9oT3SWOch-B0mL&index=1
                            if (str_contains($dl, 'youtube')) {
                                $tv['directLink'][$index]['logoPath'] = '/images/series/logos/youtube-premium.png';
                                $tv['directLink'][$index]['name'] = 'Youtube Premium';
                            }
                            // https://www.netflix.com/title/81243969
                            if (str_contains($dl, 'netflix')) {
                                $tv['directLink'][$index]['logoPath'] = '/images/series/logos/netflix.png';
                                $tv['directLink'][$index]['name'] = 'Netflix';
                            }
                            // https://www.viki.com/tv/39525c-star-in-my-mind
                            if (str_contains($dl, 'viki')) {
                                $tv['directLink'][$index]['logoPath'] = '/images/series/logos/viki.jpg';
                                $tv['directLink'][$index]['name'] = 'Viki';
                            }
                            // https://tv.apple.com/fr/episode/red-moon/umc.cmc.58f7yvuesckz5c6bnprcgkb8s?at=1000l3V2
                            if (str_contains($dl, 'apple')) {
                                $tv['directLink'][$index]['logoPath'] = '/images/series/logos/apple-tv.jpg';
                                $tv['directLink'][$index]['name'] = 'Apple TV Plus';
                            }

                            if (!key_exists('logoPath', $tv['directLink'][0])) {
                                $tv['directLink'][$index]['logoPath'] = '/images/series/logos/vod.jpg';
                                $tv['directLink'][$index]['name'] = 'Direct Link';
                            }
                            $index++;
                        }
                    }
                }
            }
            $providersMatches = [
                'disney' => 337, // Disney Plus
                'netflix' => 8, // Netflix
                'prime' => 119, // Amazon Prime Video
                'canal' => 381, // Canal+
                'ocs' => 56, // OCS Go
                'apple' => 350, // Apple TV Plus
//                    '' => 3, // Google Play Movies
//                    '' => 193, // SFR Play
//                    '' => 147, // Sixplay
//                    '' => 61, // Orange VOD
//                    '' => 236, // France TV
//                    '' => 234, // Arte
//                    '' => 223, // Hayu
//                    '' => 68, // Microsoft Store
                'youtube' => 188, // YouTube Premium
//                    '' => 58, // Canal VOD
//                    '' => 59, // Bbox VOD
//                    '' => 177, // Pantaflix
                'viki' => 35, // Rakuten TV
//                    '' => 415, // Anime Digital Networks
//                    '' => 190, // Curiosity Stream
//                    '' => 475, // DOCSVILLE
//                    '' => 538, // Plex
//                    '' => 546, // WOW Presents Plus
//                    '' => 551, // Magellan TV
//                    '' => 444, // Dekkoo
//                    '' => 315, // Hoichoi
//                    '' => 10, // Amazon Video
//                    '' => 300, // Pluto TV
//                    '' => 685, // OCS Amazon Channel
//                    '' => 588, // MGM Amazon Channel
//                    '' => 262, // Noggin Amazon Channel
//                    '' => 296, // Hayu Amazon Channel
//                    '' => 692, // Cultpix
//                    '' => 701, // FilmBox+
//                    '' => 1733, // Action Max Amazon Channel
//                    '' => 1735, // Insomnia Amazon Channel
//                    '' => 1737, // INA madelen Amazon Channel
//                    '' => 1738, // Benshi Amazon Channel
//                    '' => 309, // Sun Nxt
//                    '' => 445, // Classix
//                    '' => 1796, // Netflix basic with Ads
//                    '' => 531, // Paramount Plus
//                    '' => 582, // Paramount+ Amazon Channel
//                    '' => 1853, // Paramount Plus Apple TV Channel
//                    '' => 1870, // Pass Warner Amazon Channel
//                    '' => 283, // Crunchyroll
//                    '' => 1887, // BrutX Amazon Channel
//                    '' => 1888, // Animation Digital Network Amazon Channel
//                    '' => 1889, // Universal+ Amazon Channel
//                    '' => 1715, // Shahid VIP
//                    '' => 1967, // Molotov TV
//                    '' => 542 // filmfriend
            ];
            if (count($providersFlatrate ?? []) && count($watchProviderList ?? [])) {
                $justWatchPage = $this->TMDBService->justWatchPage($tv['id']);
                preg_match_all('/https:\/\/click.+r=(http.+uct_country=fr)/', $justWatchPage, $matches);
                $justWatchPage = "";
                $matches = array_map(function ($match) {
                    return urldecode($match);
                }, array_unique($matches[1]));

                if (count($matches)) {
                    foreach ($matches as $match) {
                        $url = $match;
//                        dump($url);
                        if (str_contains($url, "netflix")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        if (str_contains($url, "primevideo")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        if (str_contains($url, "disneyplus")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        if (str_contains($url, "youtube")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        if (str_contains($url, "viki")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        if (str_contains($url, "apple")) {
                            $url = preg_replace('/&.*$/', '', $url);
                        }
                        $urls[] = $url;

                        $logoPath = null;
                        $name = null;
                        foreach ($providersMatches as $providerMatch => $providerId) {
                            if (str_contains($match, $providerMatch)) {
                                $logoPath = $watchProviderList[$providerId]['logo_path'];
                                $name = $watchProviderList[$providerId]['provider_name'];
                                break;
                            }
                        }
                        $tv['directLink'][] = ['url' => $url, 'logoPath' => $logoPath, 'name' => $name];
                        if ($dls == null) {
                            $serie->setDirectLink($serie->getDirectLink() ? $serie->getDirectLink() . ',' : '' . $url);
                            $this->serieRepository->save($serie);
                        }
                    }
                    if ($dls == null) {
                        $this->serieRepository->flush();
                    }
                }
            }
//            dump($tv['directLink']);
            $foundUrls = [];
            $tv['directLink'] = array_filter($tv['directLink'], function ($dl) use (&$foundUrls) {
                if (!in_array($dl['url'], $foundUrls)) {
                    $foundUrls[] = $dl['url'];
                    $added = true;
                } else {
                    $added = false;
                }
                return $added;
            });

            $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);

            if ($serieViewing == null) {
                $serieViewing = $this->createSerieViewing($user, $tv, $serie);
            } else {
                $whatsNew = $this->whatsNew($tv, $serie, $serieViewing);
                $serieViewing = $this->updateSerieViewing($serieViewing, $tv, false, true);
            }
            $serieOverview = $serie->getOverview();
            if (strlen($tv['overview']) == 0) {
                $tv['overview'] = $serieOverview;
            }

            $nextEpisodeToWatch = $this->getNextEpisodeToWatch($serieViewing, $locale);

            if (!count($serie->getSerieCasts()) || ($whatsNew && (key_exists('season', $whatsNew) || key_exists('episode', $whatsNew)))) {
                $this->updateTvCast($tv, $serie);
            }
            $cast = $this->getCast($serie);
            $credits['cast'] = $cast;

            $seasonsWithAView = [];

            foreach ($tv['seasons'] as $season) {
                if ($season['season_number'] == 0) continue;

                $seasonWithAView = $season;
                $seasonViewing = $serieViewing->getSeasonByNumber($season['season_number']);
                $seasonWithAView['seasonViewing'] = $seasonViewing;
                if ($serieViewing->isTimeShifted()) {
                    $airDate = $this->dateService->newDate($season['air_date'], $user->getTimezone(), true);
                    $airDate = $airDate->modify('+1 day');
                    $seasonWithAView['air_date'] = $airDate->format('Y-m-d');
                }
                $seasonsWithAView[] = $seasonWithAView;
            }
            $tv['seasons'] = $seasonsWithAView;
        } else {
            $tv['seriePosters'] = [];
            $tv['serieBackdrops'] = [];
        }

        $tv['seasons'] = array_map(function ($season) use ($tv, $locale) {
            $standing = $this->TMDBService->getTvSeason($tv['id'], $season['season_number'], $locale);
            $seasonTMDB = json_decode($standing, true);
            $season['episodes'] = $seasonTMDB['episodes'];
//            $episodeCast =
            return $season;
        }, $tv['seasons']);

        $ygg = str_replace(' ', '+', $tv['name']);
        if (key_exists('localized_name', $tv) && $tv['localized_name'])
            $yggOriginal = str_replace(' ', '+', $tv['localized_name']);
        else
            $yggOriginal = str_replace(' ', '+', $tv['original_name']);

        $addThisSeries = !$serieViewing;

        $alert = $serieViewing ? $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]) : null;

        // Breadcrumb - if $from == self::SERIES_FROM_COUNTRY, $query = country
        $breadcrumb = $this->breadcrumb($from, $tv, null, null, $query);
        $extra = $request->query->get('extra');
        if ($extra) {
            $breadcrumb[] = [
                'url' => $this->generateUrl('app_series_today') . '?d=' . $request->query->get('d'),
                'name' => $extra
            ];
        }
        $this->savePoster($tv['poster_path'], $imgConfig['url'] . $imgConfig['poster_sizes'][5]);

//        dump([
//            'tv' => $tv,
//            'watchProviders' => $watchProviders,
//            'providersFlatrate' => $providersFlatrate,
//            'watchProviderList' => $watchProviderList,
//            'breadcrumb' => $breadcrumb,
//            'credits' => $credits,
//        ]);
        return $this->render('series/show.html.twig', [
            'serie' => $tv,
            'serieId' => $serie?->getId(),
            'addThisSeries' => $addThisSeries,
            'currentYear' => $this->dateService->newDate('now', $user ? $user->getTimezone() : 'Europe/Paris', true)->format('Y'),
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

    public function breadcrumb($from, $serie = null, $season = null, $episode = null, $country = 'FR'): array
    {
        $kind = 'show';
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
            case self::SERIES_FROM_COUNTRY:
                $baseUrl = $this->generateUrl("app_series_from_country", ['countryCode' => $country]);
                $baseName = $this->translator->trans("Series") . " - " . Countries::getName($country);
                break;
            case self::EPISODES_OF_THE_DAY:
                $baseUrl = $this->generateUrl("app_series_today");
                $baseName = $this->translator->trans("My series airing today");
                break;
            case self::EPISODES_OF_THE_WEEK:
                $baseUrl = $this->generateUrl("app_series_this_week");
                $baseName = $this->translator->trans("Episodes of the week");
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
                $kind = 'tmdb';
                break;
            case self::MY_EVENTS:
                $baseUrl = $this->generateUrl("app_event");
                $baseName = $this->translator->trans("My events");
                break;
            case 'app_home':
                $baseUrl = $this->generateUrl("app_home");
                $baseName = $this->translator->trans("Home");
                $kind = 'tmdb';
                break;
            case self::SERIES_FILTER:
                $baseUrl = $this->generateUrl("app_series_filter");
                $baseName = $this->translator->trans("Filter");
                $kind = 'tmdb';
                break;
            case self::POPULAR_SERIES:
            default:
                $baseUrl = $this->generateUrl("app_series_popular");
                $baseName = $this->translator->trans("Popular series");
                $kind = 'tmdb';
                break;
        }
        if ($serie) {
            if ($kind === 'show') {
                $serieDb = $this->serieRepository->findOneBy(['serieId' => $serie['id']]);
                if ($serieDb) {
                    $id = $serieDb->getId();
                } else {
                    $id = $serie['id'];
                    $kind = 'tmdb';
                }
            } else {
                $id = $serie['id'];
            }
        }

        $breadcrumb = [
            [
                'name' => $baseName,
                'url' => $baseUrl,
            ]
        ];
        if ($serie) {
            $localizedName = $serie['localized_name'] ?? null;
            $breadcrumb[] = [
                'name' => $localizedName ? (strlen($localizedName) ? $localizedName : $serie['name']) : $serie['name'],
                'url' => $this->generateUrl('app_series_' . $kind, ['id' => $id]) . '?from=' . $from . ($from === self::SERIES_FROM_COUNTRY ? '&c=' . $country : ''),
            ];
        }
        if ($season) {
            $breadcrumb[] = [
                'name' => $this->translator->trans('Season') . ' ' . $season['season_number'],
                'url' => $this->generateUrl('app_series_tmdb_season', ['id' => $serie['id'], 'seasonNumber' => $season['season_number']]) . '?from=' . $from . ($from === self::SERIES_FROM_COUNTRY ? '&c=' . $country : ''),
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

    public function seasonsCookie(): array
    {
        if (isset($_COOKIE['series_seasons'])) {
            $seasonsCookie = json_decode($_COOKIE['series_seasons'], true);
//            dump(['$_COOKIE' => $_COOKIE, 'get seasonsCookie' => $seasonsCookie]);
        } else {
            $seasonsCookie = [
                'layout' => 'list',         // roomy, compact, list
                'roomySize' => .75,         // 0.5 -> 3
                'graph' => 'plot',          // plot, bar
                'grid' => 'grid',           // grid, none
                'coloredGrid' => 'color'    // color, none
            ];
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

    public function seasonLocalizedOverview(int $id, array $season, int $seasonNumber, string $locale): array
    {
        $localized = true;
        $localizedOverview = '';
        $localizedResult = 'No need to translate';
        if (!$this->seasonIsThereSomeOverviews($season)) {
            $standing = $this->TMDBService->getTvSeason($id, $seasonNumber, '', ['credits', 'watch/providers']);
            $internationalSeason = json_decode($standing, true);
            if ($this->seasonIsThereSomeOverviews($internationalSeason)) {
                $internationalSeason['watch/providers'] = $season['watch/providers'];
                $season = $internationalSeason;
                $localized = false;
                if (strlen($season['overview'])) {
                    // Récupérer APP_ENV depuis le fichier .env
//                    $env = $_ENV['APP_ENV'];
                    try {
                        $usage = $this->deeplTranslator->translator->getUsage();
                        if ($usage->character->count + strlen($season['overview']) < $usage->character->limit) {
//                            if ($env === 'prod')
                            $localizedOverview = $this->deeplTranslator->translator->translateText($internationalSeason['overview'], null, $locale);
//                            else
//                                $localizedOverview = $season['overview'];
                            $localizedResult = 'Translated';
                        } else {
                            $localizedResult = 'Limit exceeded';
                        }
//                    dump([
//                        'usage' => $usage->character->count,
//                        'limit' => $usage->character->limit,
//                        'localizedResult' => $localizedResult,
//                        'localizedOverview' => $localizedOverview
//                    ]);
                    } catch (DeepLException $e) {
                        $localizedResult = 'Error: code ' . $e->getCode() . ', message: ' . $e->getMessage();
                        $usage = [
                            'character' => [
                                'count' => 0,
                                'limit' => 500000
                            ]
                        ];
                    }
                    // for now, we don't use deepl translator
                    // $localizedOverview = sprintf('No translation in %s mode (%d / %d -> %d%%) - %s - %s', $env, $usage->character->count, $usage->character->limit, intval(100 * $usage->character->count / $usage->character->limit), $localizedResult, $internationalSeason['overview']);
                }
            }
        }

        return [
            'localized' => $localized,
            'localizedOverview' => $localizedOverview,
            'localizedResult' => $localizedResult,
            'usage' => $usage ?? null];
    }

    public function seasonIsThereSomeOverviews($season): bool
    {
        $episodeOverviewLength = 0;
        if ($season['episodes']) {
            foreach ($season['episodes'] as $episode) {
                $episodeOverviewLength = max($episodeOverviewLength, strlen($episode['overview']));
            }
        }
        return $episodeOverviewLength > 0;
    }

    public function seasonWatchProviders(int $id, array $season, string $language = "fr-FR", string $country = "FR"): array
    {
        $imageConfig = $this->imageConfiguration->getConfig();
        // Les fournisseurs de streaming de la série du pays ($country)
        $watchProviders = $season['watch/providers'];
        $watchProviders = array_key_exists($country, $watchProviders['results']) ? $watchProviders['results'][$country] : null;

        // if there is no data for the season, we try to get the data for the serie
        // May occurs for a newly added season 2+ of a serie
        if (!$watchProviders) {
            $watchProviders = $this->TMDBService->getTvWatchProviders($id);
            $watchProviders = json_decode($watchProviders, true);
            $watchProviders = array_key_exists($country, $watchProviders['results']) ? $watchProviders['results'][$country] : null;
        }

        if ($watchProviders) {
            $array1 = $this->getProviders($watchProviders, 'buy', $imageConfig, []);
            $array2 = $this->getProviders($watchProviders, 'flatrate', $imageConfig, $array1);
            $array3 = $this->getProviders($watchProviders, 'free', $imageConfig, $array2);
            $watchProviders = array_filter($array3);
        }
        $allWatchProviders = json_decode($this->TMDBService->getTvWatchProviderList($language, $country), true);
        $allWatchProviders = $allWatchProviders['results'];
        $allWatchProviders = array_map(function ($provider) use ($imageConfig) {
            $provider['logo_path'] = $this->fullUrl('logo', 1, $provider['logo_path'], 'no_logo.png', $imageConfig);
            return $provider;
        }, $allWatchProviders);
        usort($allWatchProviders, function ($a, $b) {
            return strcmp($a['provider_name'], $b['provider_name']);
        });
        $temp = [];
        foreach ($allWatchProviders as $provider) {
            $temp[$provider['provider_id']] = $provider;
        }
        $allWatchProviders = $temp;
        $allWatchProviders[99999] = [
            "display_priorities" => [],
            "display_priority" => 61,
            "logo_path" => "/images/series/logos/yggland.png",
            "provider_name" => "yggtorrent",
            "provider_id" => 99999,
        ];
//        dump($allWatchProviders);
        return [
            'seasonWatchProviders' => $watchProviders,
            'allWatchProviders' => $allWatchProviders,
        ];
    }

    public function seasonEpisodeVotes(array $episodes): array
    {
        $episodesVotes = [];
        foreach ($episodes as $episode) {
            if (array_key_exists('viewing', $episode)) {
                /** @var EpisodeViewing $episodeViewing */
                $episodeViewing = $episode['viewing'];
                $episodesVotes[] = ['number' => $episodeViewing->getEpisodeNumber(), 'vote' => $episodeViewing->getVote()];
            }
        }
        return $episodesVotes;
    }

    public function seasonCheckEpisodeDates(User $user, array $episodes, bool $isShifted): array
    {
        $modifications = [];
        $newEpisodes = [];
        foreach ($episodes as $episode) {
            if (array_key_exists('viewing', $episode)) {
                /** @var EpisodeViewing $episodeViewing */
                $episodeViewing = $episode['viewing'];
                if ($episode['air_date']) {
                    // La date de diffusion peut changer par rapport au calendrier initial de la série / saison
                    $airDate = $this->dateService->newDateImmutable($episode['air_date'], $user->getTimezone(), true)->modify($isShifted ? "-1 day" : "0 day");
                    if ($episodeViewing->getAirDate()?->format('Y-m-d') != $airDate->format('Y-m-d')) {
                        $modifications[] = [
                            'episode' => $episode['episode_number'],
                            'episode air date' => $episode['air_date'],
                            'airDate' => $airDate,
                            'viewing air date' => $episodeViewing->getAirDate(),
                        ];
                        $episodeViewing->setAirDate($airDate);
                        $this->episodeViewingRepository->save($episodeViewing, true);
                    }
                } else {
                    if ($episodeViewing->getAirDate()) {
                        $episode['air_date'] = $episodeViewing->getAirDate()->format('Y-m-d');
                    }
                }
            }
            $newEpisodes[] = $episode;
        }
        return [$newEpisodes, $modifications];
    }

    public function getProviders($watchProviders, $type, $imgConfig, $providers, $country = 'FR', $indexed = true): array
    {
        if ($type) {
            if (key_exists($type, $watchProviders)) {
                foreach ($watchProviders[$type] as $provider) {
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
        /** @var Serie $dbSeries */
        $dbSeries = $this->serieRepository->findOneBy(['serieId' => $id]);

        if ($dbSeries == null) {
            $standing = $this->TMDBService->getTv($id, $locale);
            $tmdbSerie = json_decode($standing, true);
            $serie['id'] = $tmdbSerie['id'];
            $serie['name'] = $tmdbSerie['name'];
            $serie['backdropPath'] = $tmdbSerie['backdrop_path'];
            $serie['firstDateAir'] = $tmdbSerie['last_air_date'];
            $serie['posterPath'] = $tmdbSerie['poster_path'];
            $serie['localized_name'] = null;
            $serie['userSerie'] = null;
            $serie['userSerieViewing'] = null;
            $serie['alternate_overviews'] = [];
        } else {
            $serie['id'] = $dbSeries->getSerieId();
            $serie['name'] = $dbSeries->getName();
            $serie['backdropPath'] = $dbSeries->getBackdropPath();
            $serie['firstDateAir'] = $dbSeries->getFirstDateAir();
            $serie['posterPath'] = $dbSeries->getPosterPath();
            $serie['localized_name'] = $dbSeries->getSerieLocalizedName()?->getName();
            $serie['userSerie'] = $dbSeries;
            if ($user != null) {
                $serie['userSerieViewing'] = $this->serieViewingRepository->findOneBy(['serie' => $dbSeries, 'user' => $user]);
            } else {
                $serie['userSerieViewing'] = null;
            }
            $serie['alternate_overviews'] = array_filter($dbSeries->getSeriesAlternateOverviews()->toArray(), function ($overview) use ($locale) {
                return $overview->getLocale() == $locale;
            });
        }

        return $serie;
    }

    public function getNextEpisodeToWatch(SerieViewing $serieViewing, $locale): ?array
    {
        /** @var User $user */
        $user = $this->getUser();

        $lastNotViewedEpisode = null;
        $seasons = $serieViewing->getSeasons();

        foreach ($seasons as $season) {
            if ($season->getSeasonNumber() && !$season->isSeasonCompleted()) {
                $episodes = $season->getEpisodes();
                foreach ($episodes as $episode) {
                    if (!$episode->isViewed()) {
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
                $airDate = $this->dateService->newDateImmutable($airDate->format('Y-m-d'), $user->getTimezone(), true);

                return [
                    'episodeNumber' => $episodeNumber,
                    'seasonNumber' => $seasonNumber,
                    'airDate' => $airDate,
                ];
            }

            $serieId = $serieViewing->getSerie()->getSerieId();
            $standing = $this->TMDBService->getTvEpisode($serieId, $seasonNumber, $episodeNumber, $locale);
            $tmdbEpisode = json_decode($standing, true);

            $airDate = null;

            if ($tmdbEpisode == null) {
                return [
                    'episodeNumber' => $episodeNumber,
                    'seasonNumber' => $seasonNumber,
                    'airDate' => $airDate,
                ];
            }

            if ($tmdbEpisode['air_date'] == null) {
                return [
                    'episodeNumber' => $tmdbEpisode['episode_number'],
                    'seasonNumber' => $tmdbEpisode['season_number'],
                    'airDate' => $airDate,
                ];
            }

            $airDate = $this->dateService->newDateImmutable($tmdbEpisode['air_date'], $user->getTimezone(), true);
            if ($serieViewing->isTimeShifted()) {
                $airDate = $airDate->modify('+1 day');
            }
            $lastNotViewedEpisode->setAirDate($airDate);
            $this->episodeViewingRepository->save($lastNotViewedEpisode, true);

            return [
                'episodeNumber' => $tmdbEpisode['episode_number'],
                'seasonNumber' => $tmdbEpisode['season_number'],
                'airDate' => $airDate,
            ];
        }
        return null;
    }

    public function getCast(Serie $serie): array
    {
        $serieCastArray = $this->serieCastRepository->getSerieCast($serie->getId());

        $cast = array_map(function ($serieCast) {
            $c = $serieCast;
            // $serieCast['episodes'] = "[[1,1],[1,2],[1,3],[1,4],[1,5],[1,6],[1,7],[1,8]]";
            $episodesArray = json_decode($serieCast['episodes']);
            $count = $episodesArray ? count($episodesArray) : 0;
            $c['episodes'] = $count . ' ' . $this->translator->trans($count > 1 ? 'episodes' : 'episode');
            $episodes = [];
            foreach ($episodesArray as $episode) {
                $episodes[] = sprintf('S%02dE%02d', $episode[0], $episode[1]);
            }
            $c['episodesString'] = implode(', ', $episodes);
            return $c;
        }, $serieCastArray);

        // on replace les personnages récurrents en tête de liste
        usort($cast, function ($a, $b) {
            return $b['recurring_character'] <=> $a['recurring_character'];
        });

        return $cast;
    }

    public function updateTvCast($tv, $serie, $verbose = false): void
    {
        if ($verbose) $this->messages = [];

        $recurringCharacters = $tv['credits']['cast'];

        foreach ($tv['seasons'] as $s) {
            $seasonNumber = $s['season_number'];
            if ($seasonNumber == 0) { // 21/12/2022 : plus d'épisodes spéciaux
                continue;
            }
            $episodeCount = $s['episode_count'];

            for ($episodeNumber = 1; $episodeNumber <= $episodeCount; $episodeNumber++) {
                $standing = $this->TMDBService->getTvEpisodeCredits($tv['id'], $seasonNumber, $episodeNumber, 'fr');
                $credits = json_decode($standing, true);

                if ($credits) {
                    $casting = [];
                    if ($credits['cast']) {
                        $arr = array_map(function ($cast) use ($recurringCharacters) {
                            $cast['recurring_character'] = $this->inTvCast($recurringCharacters, $cast['id']);
                            $cast['guest_star'] = false;
                            return $cast;
                        }, $credits['cast']);
                        $casting = array_merge($casting, $arr);
                    }
                    if ($credits['guest_stars']) {
                        $arr = array_map(function ($cast) {
                            $cast['recurring_character'] = false;
                            $cast['guest_star'] = true;
                            return $cast;
                        }, $credits['guest_stars']);
                        $casting = array_merge($casting, $arr);
                    }
                    foreach ($casting as $cast) {
                        $this->episodesCast($cast, $serie, $seasonNumber, $episodeNumber, $verbose);
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

    public function episodesCast($cast, $serie, $seasonNumber, $episodeNumber, $verbose = false): void
    {
        $dbCast = $this->castRepository->findOneBy(['tmdbId' => $cast['id']]);
        $serieCast = null;
        if ($dbCast) {
            $serieCast = $this->serieCastRepository->findOneBy(['serie' => $serie, 'cast' => $dbCast]);
        }
        if ($serieCast === null) {
            $serieCast = $this->createSerieCast($cast, $dbCast, $serie, $verbose);
        }
//        if ($verbose) {
//            $episodesArray = array_merge($serieCast->getEpisodes());
//        }
        $serieCast->addEpisode($seasonNumber, $episodeNumber);
//        if ($verbose) {
//            $episodesArray = array_diff($serieCast->getEpisodes(), $episodesArray);
//            $this->messages[] = "New episodes for " . $cast['name'] . ' : ' . implode(', ', $episodesArray);
//        }
    }

    public function createSerieCast($cast, $dbCast, $serie, $verbose): SerieCast
    {
        if ($dbCast === null) {
            $dbCast = new Cast($cast['id'], $cast['name'], $cast['profile_path']);
            $this->castRepository->save($dbCast, true);
            if ($verbose) $this->messages[] = "New cast " . $cast['name'];
        }
        $serieCast = new SerieCast($serie, $dbCast);
        $serieCast->setKnownForDepartment($cast['known_for_department']);
        $serieCast->setCharacterName($cast['character']);
        $serieCast->setRecurringCharacter($cast['recurring_character']);
        $serieCast->setGuestStar($cast['guest_star']);
        $this->serieCastRepository->save($serieCast, true);
        $this->serieRepository->save($serie->addSerieCast($serieCast), true);

        if ($verbose) $this->messages[] = "New character " . $cast['character'] . ' by ' . $cast['name'];

        return $serieCast;
    }

    public function whatsNew(array $tv, Serie $serie, SerieViewing $serieViewing): array|null
    {
        /** @var User $user */
        $user = $this->getUser();
        $timezone = $user ? $user->getTimezone() : 'Europe/Paris';

        $whatsNew = [];
        $imgConfig = $this->imageConfiguration->getConfig();
        $modified = false;
        /*
         *  -name: string
         *  -posterPath: string
         *  -backdropPath: string
         *  -firstDateAir: DateTimeImmutable
         *  -status: string
         *  -overview: string
            -networks: Collection
         *  -numberOfEpisodes: number
         *  -numberOfSeasons: number
         *  -originalName: string
         */
        if ($serie->getPosterPath() !== $tv['poster_path']) {
            $seriePosters = array_map(fn($poster) => $poster->getPosterPath(), $serie->getSeriePosters()->toArray());

            if ($tv['poster_path'] && !in_array($tv['poster_path'], $seriePosters)) {
                $this->addSeriePoster($serie, $tv['poster_path'], $imgConfig);
                $whatsNew['poster_path'] = $this->translator->trans('New poster');
            } else {
                $whatsNew['poster_path'] = $this->translator->trans('New poster (previously added)');
            }
            $serie->setPosterPath($tv['poster_path']);
            $modified = true;
        }
        if ($serie->getBackdropPath() !== $tv['backdrop_path']) {
            $serieBackdrops = array_map(fn($backdrop) => $backdrop->getBackdropPath(), $serie->getSerieBackdrops()->toArray());

            if ($tv['backdrop_path'] && !in_array($tv['backdrop_path'], $serieBackdrops)) {
                $this->addSerieBackdrop($serie, $tv['backdrop_path']);
                $whatsNew['backdrop_path'] = $this->translator->trans('New backdrop');
            } else {
                $whatsNew['backdrop_path'] = $this->translator->trans('New backdrop (previously added)');
            }
            $serie->setBackdropPath($tv['backdrop_path']);
            $modified = true;
        }

//        dump(['seriePosters' => array_map(fn($poster) => $poster->getPosterPath(), $serie->getSeriePosters()->toArray())]);
//        dump(['serieBackdrops' => array_map(fn($backdrop) => $backdrop->getBackdropPath(), $serie->getSerieBackdrops()->toArray())]);

        $firstDateAir = $tv['first_air_date'];
        if ($firstDateAir !== "") {
            $firstDateAirTMDB = $this->dateService->newDateImmutable($firstDateAir, $timezone, true);
            $firstDateAirDB = $serie->getFirstDateAir()?->setTimezone(new DateTimeZone($timezone))->setTime(0, 0);
            if ($firstDateAirTMDB != $firstDateAirDB) {
                $whatsNew['first_date_air'] = $this->translator->trans('New date') . ' (' . $firstDateAir . ')';
                $serie->setFirstDateAir($firstDateAirTMDB);
                $modified = true;
            }
        }
        $serieOverview = $serie->getOverview();
        if ($serieOverview !== $tv['overview']) {
            if (strlen($tv['overview']) > 0 && strlen($serieOverview) == 0) {
                $serie->setOverview($tv['overview']);
                $whatsNew['overview'] = $this->translator->trans('New overview');
                $modified = true;
            }
        }
        if ($serie->getNumberOfSeasons() !== $tv['number_of_seasons']) {
            $delta = $tv['number_of_seasons'] - $serie->getNumberOfSeasons();
            $whatsNew['season'] = $this->translator->trans('New season from TMDB', ['%count%' => $delta]);

            $serie->setNumberOfSeasons($tv['number_of_seasons']);
            $modified = true;
        }
        if ($serie->getNumberOfEpisodes() !== $tv['number_of_episodes']) {
            $delta = $tv['number_of_episodes'] - $serie->getNumberOfEpisodes();
            $whatsNew['episode'] = $this->translator->trans('New episode from TMDB', ['%count%' => $delta]);

            $serie->setNumberOfEpisodes($tv['number_of_episodes']);
            $modified = true;
        }
        if ($serie->getStatus() !== $tv['status']) {
            $whatsNew['status'] = $this->translator->trans('New status') . ' (' . $tv['status'] . ')';

            $serie->setStatus($tv['status']);
            $modified = true;
        }
        if ($serie->getName() !== $tv['name']) {
            $whatsNew['name'] = $this->translator->trans('New name') . ' (" ' . $serie->getName() . ' ")';

            $serie->setName($tv['name']);
            $modified = true;
        }
        if ($serie->getOriginalName() !== $tv['original_name']) {
            $whatsNew['original_name'] = $this->translator->trans('New original name') . ' (" ' . $serie->getOriginalName() . ' ")';

            $serie->setOriginalName($tv['original_name']);
            $modified = true;
        }

        if ($modified) {
            $now = $this->dateService->newDate('now', $timezone);
            $serieViewing->setModifiedAt($now);
            $this->serieViewingRepository->save($serieViewing);

            $serie->setUpdatedAt($now);
            $this->serieRepository->save($serie, true);
            return $whatsNew;
        }
        return null;
    }

    public function addSeriePoster(Serie $serie, string $posterPath, array $imgConfig): void
    {
        $seriePoster = new SeriePoster($serie, $posterPath);
        $this->seriePosterRepository->save($seriePoster, true);
        $serie->addSeriePoster($seriePoster);
        $this->savePoster($posterPath, $imgConfig['url'] . $imgConfig['poster_sizes'][3]);
    }

    public function addSerieBackdrop(Serie $serie, string $backdropPath): void
    {
        $serieBackdrop = new SerieBackdrop($serie, $backdropPath);
        $this->serieBackdropRepository->save($serieBackdrop, true);
        $serie->addSerieBackdrop($serieBackdrop);
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

        /** @var User $user */
        $user = $this->getUser();
        $serie = $this->serieRepository->find($serieId);
        $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
        $episodeViewings = $this->getEpisodeViewings($serieViewing, $season, $episode, $allBefore);
        $locale = $request->getLocale();

        if ($newValue) {
            $deviceType = $request->query->getAlpha('device-type');
            if ($deviceType == '') {
                $deviceType = null;
            }
            $networkType = $request->query->getAlpha('network-type');
            if ($networkType == '') {
                $networkType = null;
            }
            $networkId = $request->query->getInt('network-id');

            /** @var EpisodeViewing $episode */
            foreach ($episodeViewings as $episode) {

                if ($episode->getViewedAt() == null) {
                    $episodeTmdb = null;
                    if (!$episode->getAirDate()) {
                        $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);

                        if ($episodeTmdb && $episodeTmdb['air_date'] != null) {
                            $episode->setAirDate($this->dateService->newDateImmutable($episodeTmdb['air_date'], $user->getTimezone(), true));
                        }
                    }

                    $episode->setNetworkId($networkId ?: null);
                    $episode->setNetworkType($networkType);
                    $episode->setDeviceType($deviceType);
                    if ($liveWatch) {
                        if ($episode->getAirDate()) {
                            $episode->setViewedAt($episode->getAirDate());
                        } else {
                            if (!$episodeTmdb) {
                                $episodeTmdb = json_decode($this->TMDBService->getTvEpisode($serie->getSerieId(), $episode->getSeason()->getSeasonNumber(), $episode->getEpisodeNumber(), $request->getLocale()), true);
                            }
                            if ($episodeTmdb) {
                                $episode->setViewedAt($this->dateService->newDateImmutable($episodeTmdb['air_date'], $user->getTimezone()));
                            }
                        }
                    } else {
                        $episode->setViewedAt($this->dateService->newDateImmutable('now', $user->getTimezone()));
                    }
                    $episode->setNumberOfView(1);

                    $this->episodeViewingRepository->save($episode, true);
                }
            }

            $this->viewingCompleted($serieViewing);
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
                    $this->dateService->newDateImmutable($episodeTmdb['air_date'], $user->getTimezone());
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
            $serieViewing->setModifiedAt($this->dateService->newDate('now', $user->getTimezone()));
        } else {
            $createdAt = $this->getSerieViewingCreatedAt($serieViewing, $request);
            if ($createdAt) {
                $modifiedAt = $this->dateService->getNow($user->getTimezone());
                $serieViewing->setModifiedAt($modifiedAt->setTimestamp($createdAt->getTimestamp()));
            }
        }
        $this->serieViewingRepository->save($serieViewing, true);
        $this->setViewedEpisodeCount($serieViewing);

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
                $s['episode_count'] = $seasonViewing->getEpisodeCount();

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

    public function getSerieViewingCreatedAt($serieViewing, $request): DateTimeImmutable|null
    {
        /** @var User $user */
        $user = $this->getUser();

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
            $createdAt = $this->dateService->newDateImmutable($firstEpisode['air_date'], $user->getTimezone());
            $serieViewing->setCreatedAt($createdAt);
            return $createdAt;
        }

        $standing = $this->TMDBService->getTv($serie->getSerieId(), $request->getLocale());
        $tv = json_decode($standing, true);
        if ($tv && $tv['first_air_date']) {
            $createdAt = $this->dateService->newDateImmutable($tv['first_air_date'], $user->getTimezone());
            $serieViewing->setCreatedAt($createdAt);
        }
        return $createdAt;
    }

    public function viewingCompleted(SerieViewing $serieViewing): bool
    {
        $seasonsCompleted = 0;
        foreach ($serieViewing->getSeasons() as $season) {
            if (!$season->getSeasonNumber()) continue;
            if (!$season->getEpisodeCount()) continue; //une saison peut être annoncée/ajoutée avec zéro épisode

            $completed = $season->getViewedEpisodeCount() == $season->getEpisodeCount();
            if ($completed && !$season->isSeasonCompleted()) {
                $season->setSeasonCompleted(true);
                $this->seasonViewingRepository->save($season, true);
            }
            if ($completed) $seasonsCompleted++;
        }
//        if ($serieViewing->getNumberOfSeasons() == 0) {
//            $serieViewing->setNumberOfSeasons(count($serieViewing->getSeasons()));
//            $this->serieViewingRepository->save($serieViewing, true);
//        }
        if ($serieViewing->getNumberOfSeasons() == $seasonsCompleted) {
            if (!$serieViewing->isSerieCompleted()) {
                $serieViewing->setSerieCompleted(true);
                $this->serieViewingRepository->save($serieViewing, true);
            }
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
        if ($serieViewing->getViewedEpisodes() != $viewedEpisodeCount) {
            $serieViewing->setViewedEpisodes($viewedEpisodeCount);
            $this->serieViewingRepository->save($serieViewing, true);
        }

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

    public function updateAlert(Alert $alert, SerieViewing $serieViewing): void
    {
        $next = $serieViewing->getNextEpisodeToWatch();
        $date = $next?->getAirDate();
        if ($date) {
            $message = sprintf("%s : S%02dE%02d\n",
                $serieViewing->getSerie()->getName(),
                $serieViewing->getNextEpisodeToWatch()->getSeason()->getSeasonNumber(),
                $serieViewing->getNextEpisodeToWatch()->getEpisodeNumber());
            $date = $date->setTime(9, 0);
            $alert->setEpisodeNumber($serieViewing->getNextEpisodeToWatch()->getEpisodeNumber());
            $alert->setSeasonNumber($serieViewing->getNextEpisodeToWatch()->getSeason()->getSeasonNumber());
            $alert->setMessage($message);
            $alert->setDate($date);
            $alert->setActivated(true);
            $this->alertRepository->save($alert, true);
        } else {
            $this->alertRepository->remove($alert, true);
            $this->addFlash('info', $this->translator->trans($next ? 'No date yet for the upcoming episode' : 'No upcoming episodes for the moment'));
        }
    }

    public function mySerieIds(User|null $user): array
    {
        if ($user == null) {
            return [];
        }
        $arr = $this->serieRepository->findMySerieIds($user->getId());
        return array_column($arr, 'serie_id');
    }
}

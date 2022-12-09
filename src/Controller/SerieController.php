<?php

namespace App\Controller;

use App\Entity\EpisodeViewing;
use App\Entity\SeasonViewing;
use App\Entity\Serie;
use App\Entity\SerieViewing;
use App\Entity\Settings;
use App\Entity\User;
use App\Form\SerieSearchType;
use App\Repository\EpisodeViewingRepository;
use App\Repository\SeasonViewingRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
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
    const LATEST = 'latest';
    const SEARCH = 'search';
    const SERIE_PAGE = 'serie';

    private SerieViewingRepository $serieViewingRepository;
    private SeasonViewingRepository $seasonViewingRepository;
    private EpisodeViewingRepository $episodeViewingRepository;

    public function __construct(SerieViewingRepository $serieViewingRepository, SeasonViewingRepository $seasonViewingRepository, EpisodeViewingRepository $episodeViewingRepository)
    {
        $this->serieViewingRepository = $serieViewingRepository;
        $this->seasonViewingRepository = $seasonViewingRepository;
        $this->episodeViewingRepository = $episodeViewingRepository;
    }

    #[Route('/', name: 'app_serie_index', methods: ['GET'])]
    public function index(Request $request, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration, SettingsRepository $settingsRepository): Response
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
        // Liste des séries ajoutées par l'utilisateur pour le menu de recherche
        $list = $serieRepository->listUserSeries($user->getId());

        $series = $totalResults ? $this->getSeriesViews($user, $results) : null;
//        dump($series);

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
//            dump($leafSettings);
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
            'imageConfig' => $imageConfiguration->getConfig(),
        ]);
    }

    public function getSeriesViews($user, $results): array
    {
        $ids = '(';
        /** @var Serie $result */
        foreach ($results as $result) {
            $ids .= $result->getId() . ', ';
        }
        $ids = substr($ids, 0, strlen($ids) - 2) . ')';
        $viewings = $this->serieViewingRepository->getSeriesViewings($user, $ids);

        $series = [];
        /** @var Serie $result */
        foreach ($results as $result) {
            $serie = $this->serie2array($result);
            $serie['viewing'] = $this->getSerieViews($result, $viewings);

            $series[] = $serie;
        }
        return $series;
    }

    public function getSerieViews(Serie $result, $viewings): ?array
    {
        foreach ($viewings as $viewing) {
            if ($viewing['serie_id'] == $result->getId()) {
                return $viewing;
            }
        }
        return null;
    }

    public function serie2array(Serie $result): array
    {
        $serie['id'] = $result->getId();
        $serie['name'] = $result->getName();
        $serie['posterPath'] = $result->getPosterPath();
        $serie['backdropPath'] = $result->getBackdropPath();
        $serie['serieId'] = $result->getSerieId();
        $serie['firstDateAir'] = $result->getFirstDateAir();
        $serie['addedAt'] = $result->getAddedAt();
        $serie['updatedAt'] = $result->getUpdatedAt();
        $serie['serieCompleted'] = $result->isSerieCompleted();
        $serie['status'] = $result->getStatus();
        $serie['overview'] = $result->getOverview();
        $serie['networks'] = $result->getNetworks();
        $serie['numberOfEpisodes'] = $result->getNumberOfEpisodes();
        $serie['numberOfSeasons'] = $result->getNumberOfSeasons();
        $serie['originalName'] = $result->getOriginalName();
        $serie['modifiedAt'] = $result->getModifiedAt();

        return $serie;
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
            // // dump($page, $query, $year);
            $series = json_decode($standing, true);
            // // dump('results', $series);
        }

        /** @var User $user */
        $user = $this->getUser();
        return $this->render('serie/search.html.twig', [
            'form' => $form->createView(),
            'query' => $query,
            'year' => $year,
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($serieRepository, $user) : [],
            'pages' => [
                'page' => $page,
                'total_pages' => $series['total_pages'],
                'total_results' => $series['total_results'],
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'quotes' => (new QuoteService)->getRandomQuotes(),
            'user' => $user,
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
        // // dump($series);

        return $this->render('serie/popular.html.twig', $this->renderParams($kind, $page, $series, $serieRepository, $imageConfiguration));
    }

    public function renderParams($from, $page, $series, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): array
    {
        /** @var User $user */
        $user = $this->getUser();
        return [
            'series' => $series['results'],
            'serieIds' => $user ? $this->mySerieIds($serieRepository, $user) : [],
            'pages' => [
                'total_results' => $series['total_results'],
                'page' => $page,
                'per_page' => 20,
                'link_count' => self::LINK_COUNT,
                'paginator' => $this->paginator($series['total_results'], $page, 20, self::LINK_COUNT),
            ],
            'from' => $from,
            'user' => $user,
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

    public function createViewing($user, $tv, $serie)
    {
        $viewing = new SerieViewing();
        $viewing->setUser($user);
        $viewing->setSerie($serie);
        $viewing->setViewing($this->createViewingTab($tv));
        $viewing->setSeasonCount($tv['number_of_seasons']);
        $viewing->setViewedEpisodes(0);
        foreach ($tv['seasons'] as $s) {
            $season = new SeasonViewing($s['air_date'], $s['season_number'], $s['episode_count'], false);
            $this->seasonViewingRepository->save($season, true);
            $viewing->addSeason($season);
        }
        $seasons = $viewing->getSeasons();
        foreach ($seasons as $season) {
            for ($i = 1; $i <= $season->getEpisodeCount(); $i++) {
                $episode = new EpisodeViewing($i);
                $this->episodeViewingRepository->save($episode);
                $season->addEpisode($episode);
            }
        }
        $this->serieViewingRepository->save($viewing, true);
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
    public function show(Request $request, Serie $serie, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::MY_SERIES);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($serie->getSerieId(), $request->getLocale());
//        // dump($standing);
        $tv = json_decode($standing, true);
        // // dump($tv);
        if ($tv == null) {
            return $this->render('serie/_error.html.twig', [
                'serie' => $serie,
            ]);
        }

        return $this->getSerie($tv, $page, $from, $serie->getId(), $request, $tmdbService, $serieRepository, $serie, $imageConfiguration, $query, $year);
    }

    #[Route('/tmdb/{id}', name: 'app_serie_tmdb', methods: ['GET'])]
    public function tmdb(Request $request, $id, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $page = $request->query->getInt('p', 1);
        $from = $request->query->get('from', self::POPULAR);
        $query = $request->query->get('query', "");
        $year = $request->query->get('year', "");

        $standing = $tmdbService->getTv($id, $request->getLocale());
        $tv = json_decode($standing, true);

        return $this->getSerie($tv, $page, $from, $id, $request, $tmdbService, $serieRepository, null, $imageConfiguration, $query, $year);
    }

    #[Route('/tmdb/{id}/season/{seasonNumber}', name: 'app_serie_tmdb_season', methods: ['GET'])]
    public function season(Request $request, $id, $seasonNumber, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
    {
        $from = $request->query->get('from');
        $page = $request->query->get('p');
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $backId = $request->query->get('back');

        $serie = $this->serie($id, $tmdbService, $request->getLocale(), $serieRepository);
        $standing = $tmdbService->getTvSeason($id, $seasonNumber, $request->getLocale());
        $season = json_decode($standing, true);
        // // dump($season);
        $standing = $tmdbService->getTvSeasonCredits($id, $seasonNumber, $request->getLocale());
        $credits = json_decode($standing, true);
        // // dump($credits);
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

    public function serie($id, $tmdbService, $locale, $serieRepository): array
    {
        $serie = [];
        /** @var Serie $userSerie */
        $userSerie = $serieRepository->findOneBy(['serieId' => $id]);
        if ($userSerie == null) {
            $standing = $tmdbService->getTv($id, $locale);
            $tmdbSerie = json_decode($standing, true);
            // // dump($tmdbSerie);
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

//    #[Route('/latest/serie', name: 'app_serie_latest', methods: ['GET'])]
//    public function latest(Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, ImageConfiguration $imageConfiguration): Response
//    {
//        $standing = $tmdbService->getLatest($request->getLocale());
//        $tv = json_decode($standing, true);
//        return $this->getSerie($tv, 0, self::LATEST, 0, $request, $tmdbService, $serieRepository, null, $imageConfiguration);
//    }

    public function getSerie(array $tv, int $page, string $from, $backId, Request $request, TMDBService $tmdbService, SerieRepository $serieRepository, Serie|null $serie, ImageConfiguration $imageConfiguration, $query = "", $year = ""): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $id = $tv['id'];
        $standing = $tmdbService->getTvCredits($id, $request->getLocale());
        $credits = json_decode($standing, true);
//        dump($standing, $credits);

        $standing = $tmdbService->getTvKeywords($id, $request->getLocale());
        $keywords = json_decode($standing, true);
        $missingTranslations = $this->keywordsTranslation($keywords, $request->getLocale());

        $standing = $tmdbService->getTvWatchProviders($id);
        $temp = json_decode($standing, true);
        if ($temp && array_key_exists('FR', $temp['results'])) {
            $watchProviders = json_decode($standing, true)['results']['FR'];
        } else {
            $watchProviders = null;
        }
        $standing = $tmdbService->getTvSimilar($id);
        $similar = json_decode($standing, true);

        $index = false;
        $serieIds = [];
        if ($user) {
            // Est-ce une série ajoutée ? $index != false => Ok
            $serieIds = $this->mySerieIds($serieRepository, $user);
            if (in_array($id, $serieIds)) {
                $index = $id;
            }
        }

        $standing = $tmdbService->getTvImages($id, $request->getLocale());
        $images = json_decode($standing, true);

        $serieId = null;
        $viewing = null;
        $whatsNew = null;

        if ($index) {
            if ($serie === null) {
                $serie = $serieRepository->findOneBy(['serieId' => $id]);
            }
            if ($serie) {
                $serieId = $serie->getId();
                $whatsNew = $this->whatsNew($tv, $serie, $serieRepository);

                /** @var SerieViewing $viewing */
                $viewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
//                dump($viewing, $serie);
                if ($viewing == null) {
                    $this->createViewing($user, $tv, $serie);
                } else {
//                    $viewing->setViewing($this->updateViewing($tv, $viewing));
                    $this->updateSerieViewing($tv, $viewing);
                    $this->serieViewingRepository->save($viewing, true);
                }
            }
        }
        $ygg = str_replace(' ', '+', $tv['name']);
        $yggOriginal = str_replace(' ', '+', $tv['original_name']);

//        dump($tv);
//        dump($viewing);

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
            'viewing' => $viewing?->getViewing(),
            'viewedEpisodes' => $viewing?->getViewedEpisodes(),
            'whatsNew' => $whatsNew,
            'ygg' => $ygg,
            'yggOriginal' => $yggOriginal,
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
            $tab[] = [
                'season_number' => $season['season_number'],
                'season_completed' => false,
                'air_date' => $season['air_date'],
                'episode_count' => $season['episode_count'],
                'episodes' => array_fill(0, $season['episode_count'], false)
            ];
        }
        return $tab;
    }

    public function whatsNew($tv, $serie, $serieRepository): array|null
    {
        $whatsNew = ['episode' => 0, 'season' => 0, 'status' => "", 'original_name' => ""];
        $modified = false;
        if ($serie->getNumberOfSeasons() !== $tv['number_of_seasons']) {
            $whatsNew['season'] = $tv['number_of_seasons'] - $serie->getNumberOfSeasons();
            $modified = true;

            $serie->setNumberOfSeasons($tv['number_of_seasons']);
            $serie->setModifiedAt(new DateTime());
        }
        if ($serie->getNumberOfEpisodes() !== $tv['number_of_episodes']) {
            $whatsNew['episode'] = $tv['number_of_episodes'] - $serie->getNumberOfSeasons();
            $modified = true;

            $serie->setNumberOfEpisodes($tv['number_of_episodes']);
            $serie->setModifiedAt(new DateTime());
        }
        if ($serie->getStatus() !== $tv['status']) {
            $whatsNew['status'] = $tv['status'];
            $modified = true;

            $serie->setStatus($tv['status']);
            $serie->setModifiedAt(new DateTime());
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

    public function getSeasonViews($viewings): array
    {
        $seasonViews = [];
        foreach ($viewings as $viewing) {
            $object = new SeasonView();
            foreach ($viewing as $key => $value) {
                switch ($key) {
                    case "air_date":
                        $object->setAirDate(new DateTimeImmutable($value));
                        break;
                    case "episodes":
                        $object->setEpisodes($value);
                        break;
                    case "episode_count":
                        $object->setEpisodeCount($value);
                        break;
                    case "season_completed":
                        $object->setSeasonCompleted($value);
                        break;
                    case "season_number":
                        $object->setSeasonNumber($value);
                        break;
                }
            }
            $seasonViews[] = $object;
        }
        return $seasonViews;
    }

//    public function updateViewing($tv, SerieViewing $theViewing): array
//    {
//        $viewings = $theViewing->getViewing();
//        $seasons = $tv['seasons'];
//        $modified = false;
//
//        $seasonViews = $this->getSeasonViews($viewings);
//        dump($seasonViews);
//
//        /*
//         * Épisodes spéciaux : saison 0
//         *
//         * Si la saison comporte des épisodes spéciaux et que cette info est déjà dans la base (viewing)
//         * on met à jour viewing
//         */
//        $specialEpisodes = $seasons[0]['season_number'] == 0;
//        if ($specialEpisodes) {
//            $season = $seasons[0];
//
//            if ($viewings[0]['season_number'] == 0) {
//                /*
//                 * Le nombre d'épisodes spéciaux a augmenté ?
//                 */
//                if ($season['episode_count'] != $viewings[0]['episode_count']) {
//                    $viewings[0]['episode_count'] = $season['episode_count'];
//                    $viewings[0]['episodes'] = array_pad($viewings[0]['episodes'], $season['episode_count'], false);
//                    $viewings[0]['season_completed'] = false;
//                    $modified = true;
//                }
//                /*
//                 * L'info air_date est présente ?
//                 */
//                if (!array_key_exists('air_date', $viewings[0])) {
//                    $viewings[0]['air_date'] = $season['air_date'];
//                    $modified = true;
//                }
//            }
//        }
//
//        /*
//         * Si viewings ne comportait pas de saison 0
//         */
//        if ($viewings[0]['season_number'] != 0) {
//            $firstItem = [
//                'season_number' => 0,
//                'season_completed' => false,
//                'air_date' => null,
//                'episode_count' => 0,
//                'episodes' => []
//            ];
//            array_unshift($viewings, $firstItem);
//            $modified = true;
//        } else {
//            if (!array_key_exists('air_date', $viewings[0])) {
//                $viewings[0]['air_date'] = $specialEpisodes ? $seasons[0]['air_date'] : null;
//                $modified = true;
//            }
//        }
//
//        /*
//         * Les saisons suivantes. 'number_of_seasons' correspond au nombre de saisons, épisodes spéciaux exclus
//         */
//        $viewingCount = count($viewings);
//        /*
//         * Saison(s) supplémentaire(s)
//         */
//        if ($viewingCount < $tv['number_of_seasons'] + 1) {
//            // Dernière saison enregistrée
//            $lastViewingSeason = $viewingCount - 1;
//
//            for ($i = $lastViewingSeason + 1; $i <= $tv['number_of_seasons']; $i++) {
//                $season = $tv['seasons'][$i - 1];
//                $newItem = [
//                    'season_number' => $season['season_number'],
//                    'season_completed' => false,
//                    'air_date' => $season['air_date'],
//                    'episode_count' => $season['episode_count'],
//                    'episodes' => array_fill(0, $season['episode_count'], false)
//                ];
//                $viewings[] = $newItem;
//                $modified = true;
//            }
//        } else {
//            /*
//             * Nouveaux épisodes pour la dernière saison ?
//             */
//            $lastSeasonEpisodeCount = $tv['seasons'][$tv['number_of_seasons'] - 1]['episode_count'];
//            if ($viewings[$viewingCount - 1]['episode_count'] < $lastSeasonEpisodeCount) {
//                $viewings[$viewingCount - 1]['episode_count'] = $lastSeasonEpisodeCount;
//                $viewings[$viewingCount - 1]['episodes'] = array_pad($viewings[$viewingCount - 1]['episodes'], $lastSeasonEpisodeCount, false);
//                $modified = true;
//            }
//        }
//
//        $viewingCount = count($viewings);
//        for ($i = 1; $i < $viewingCount; $i++) {
//            if (!array_key_exists('air_date', $viewings[$i])) {
//                $viewings[$i]['air_date'] = $seasons[$i - $specialEpisodes ? 0 : 1]['air_date'];
//                $modified = true;
//            }
//        }
//        $viewed = $this->getViewedEpisodes($theViewing->getViewing());
//        if ($viewed !== $theViewing->getViewedEpisodes()) {
//            $theViewing->setViewedEpisodes($viewed);
//            $modified = true;
//        }
//
//        if ($modified) {
//            $theViewing->setViewing($viewings);
//            $this->serieViewingRepository->save($theViewing, true);
//        }
//
//        return $viewings;
//    }

    public function updateSerieViewing($tv, SerieViewing $theViewing)
    {
        foreach ($tv['seasons'] as $s) {
            $season = $theViewing->getSeasonByNumber($s['season_number']);
            if ($season === null) {
                $season = new SeasonViewing($s['air_date'], $s['season_number'], $s['episode_count'], false);
                $this->seasonViewingRepository->save($season, true);
                $theViewing->addSeason($season);
                for ($i = 1; $i <= $s['episode_count']; $i++) {
                    $episode = new EpisodeViewing($i);
                    $this->episodeViewingRepository->save($episode);
                    $season->addEpisode($episode);
                }
            } else {
                if ($season->getEpisodeCount() < $s['episode_count']) {
                    for ($i = $season->getEpisodeCount() + 1; $i <= $s['episode_count']; $i++) {
                        $episode = new EpisodeViewing($i);
                        $this->episodeViewingRepository->save($episode);
                        $season->addEpisode($episode);
                    }
                }
            }
        }
//        $seasons = $theViewing->getSeasons();
//        dump($seasons);
    }

    public function getViewedEpisodes($viewing): int
    {
        $viewed = 0;
        $seasons = $viewing;
        $count = count($seasons);
        for ($i = 1; $i < $count; $i++) {
            if (key_exists($i, $seasons)) {
                $season = $seasons[$i];
                if ($season['episode_count']) {
                    if ($season['season_completed']) {
                        $viewed += $season['episode_count'];
                    } else {
                        $count = 0;
                        foreach ($season['episodes'] as $view) if ($view) $count++;
                        $viewed += $count;
                    }
                }
            }
        }
//        // dump($viewed);
        return $viewed;
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/viewing', name: 'app_serie_viewing')]
    public function updateViewingArray(Request $request, SerieRepository $serieRepository, SerieViewingRepository $viewingRepository, TMDBService $tmdbService, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $serieId = $request->query->getInt('id');
        $season = $request->query->getInt('s');
        $episode = $request->query->getInt('e');
        $newValue = $request->query->getInt('v');

        $user = $this->getUser();
        $serie = $serieRepository->findOneBy(['serieId' => $serieId]);

        /* ---start--- entity based viewing ---start--- */
        if ($newValue) {
            $deviceType = $request->query->getAlpha('device-type');
            $networkType = $request->query->getAlpha('network-type');
            $networkId = $request->query->getInt('network-id');
            $allBefore = $request->query->getInt('all');

            $episodeViewings = $this->getEpisodeViewings($user, $serie, $season, $episode, $allBefore);
//            dump($episodeViewings, $allBefore);

            /** @var EpisodeViewing $episodeViewing */
            foreach ($episodeViewings as $episodeViewing) {

                if ($episodeViewing->getViewedAt() == null) {
                    $episodeViewing->setNetworkId($networkId ?: null);
                    $episodeViewing->setNetworkType($networkType != "" ? $networkType : null);
                    $episodeViewing->setDeviceType($deviceType != "" ? $deviceType : null);
                    $episodeViewing->setViewedAt(new DateTimeImmutable());

                    if (!$episodeViewing->getAirDate()) {
                        $episodeTmdb = json_decode($tmdbService->getTvEpisode($serieId, $episodeViewing->getSeason()->getSeasonNumber(), $episodeViewing->getEpisodeNumber(), $request->getLocale()), true);
                        $episodeViewing->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
                    }
                    $this->episodeViewingRepository->save($episodeViewing, true);
                }
            }
            /* ---start--- Season completed ? ---start--- */
            /** @var SerieViewing $serieViewing */
            $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);
            $seasonViewings = $this->seasonViewingRepository->findBy(['serie' => $serieViewing]);
            foreach ($seasonViewings as $seasonViewing) {
                $completed = true;
                $episodeViewings = $this->episodeViewingRepository->findBy(['season' => $seasonViewing]);
                foreach ($episodeViewings as $episodeViewing) {
                    if ($episodeViewing->getViewedAt() == null) {
                        $completed = false;
                        break;
                    }
                }
                $seasonViewing->setSeasonCompleted($completed);
                $this->seasonViewingRepository->save($seasonViewing, true);
            }
            /* ----end---- Season completed ? ----end---- */
        } else {
            $episodeViewing = $this->getEpisodeViewings($user, $serie, $season, $episode, false)[0];

            $episodeViewing->setViewedAt(null);
            $episodeViewing->setDeviceType(null);
            $episodeViewing->setNetworkType(null);
            $episodeViewing->setNetworkId(null);
            if (!$episodeViewing->getAirDate()) {
                $episodeTmdb = json_decode($tmdbService->getTvEpisode($serieId, $season, $episode, $request->getLocale()), true);
                $episodeViewing->setAirDate(new DateTimeImmutable($episodeTmdb['air_date']));
            }

            $this->episodeViewingRepository->save($episodeViewing, true);
        }
        /* ----end---- entity based viewing ----end---- */

        $episode = $newValue ? $episode : $episode - 1;

        $tv = json_decode($tmdbService->getTv($serieId, $request->getLocale()), true);
        $seasons = $tv['seasons'];
        $noSpecialEpisodes = $seasons[0]['season_number'] == 1 ? 1 : 0;
// // dump($seasons, $noSpecialEpisodes);
        $theViewing = $viewingRepository->findOneBy(['user' => $this->getUser(), 'serie' => $serie]);
        $viewings = $theViewing->getViewing();

        $seasonViews = $this->getSeasonViews($viewings);
//        dump($seasonViews);

        $newTab = [];
        /*
         * Épisodes spéciaux : saison 0
         */
        if ($season == 0) {
            $newEpisodes = [];
            $episode_count = $viewings[0]['episode_count'];
            $air_date = array_key_exists('air_date', $viewings[0]) ? $viewings[0]['air_date'] : $seasons[0]['air_date'];
            for ($e = 1; $e <= $episode_count; $e++) {
                $newEpisodes[] = $e <= $episode;
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
                $newTab[$i] = $viewings[$i];
                if (!array_key_exists('air_date', $viewings[$i])) $newTab[$i]['air_date'] = $seasons[$i]['air_date'];
            }
        } else {
            $newTab[0] = $viewings[0];
            if (!array_key_exists('air_date', $viewings[0])) $newTab[0]['air_date'] = $noSpecialEpisodes ? null : $seasons[0]['air_date'];
            /*
             * Les saisons précédentes
             */
            for ($s = 1; $s < $season; $s++) {
                $episode_count = $viewings[$s]['episode_count'];
                $newEpisodes = array_fill(0, $episode_count, true);
                $air_date = array_key_exists('air_date', $viewings[$s]) ? $viewings[$s]['air_date'] : $seasons[$s - $noSpecialEpisodes]['air_date'];
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
            $episode_count = $viewings[$season]['episode_count'];
            $air_date = array_key_exists('air_date', $viewings[$season]) ? $viewings[$season]['air_date'] : $seasons[$season - $noSpecialEpisodes]['air_date'];
            // dump($air_date);
            for ($e = 1; $e <= $episode_count; $e++) {
                $newEpisodes[] = $e <= $episode;
            }
            $full = !in_array(false, $newEpisodes, true);
            $newTab[] = [
                'season_number' => $s,
                'season_completed' => $full,
                'air_date' => $air_date,
                'episode_count' => $episode_count,
                'episodes' => $newEpisodes
            ];
            // // dump($newTab);
            /*
             * Les saisons suivantes
             */
            $season_count = $serie->getNumberOfSeasons();
            for ($s = $season + 1; $s <= $season_count; $s++) {
                $episode_count = $viewings[$s]['episode_count'];
                $newEpisodes = array_fill(0, $episode_count, false);
                $air_date = array_key_exists('air_date', $viewings[$s]) ? $viewings[$s]['air_date'] : $seasons[$s - $noSpecialEpisodes]['air_date'];
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
        $seasons_completed = [];
        foreach ($newTab as $tab) {
            // // dump($tab);
            if ($tab['air_date'] <= $today)
                $seasons_completed[] = !in_array(false, $tab['episodes'], true);
        }
        $serie_completed = !in_array(false, $seasons_completed, true);

        $viewed = $this->getViewedEpisodes($newTab);
        $theViewing->setViewedEpisodes($viewed);
        $theViewing->setViewing($newTab);
        $viewingRepository->save($theViewing, true);

        $serie->setUpdatedAt(new DateTime());
        $serie->setSerieCompleted($serie_completed);
        $serieRepository->save($serie, true);

        $blocks = [];
        $globalIndex = 1;
        foreach ($newTab as $tab) {
            if ($tab['episode_count']) {
                $blocks[] = [
                    'season' => $tab['season_number'],
                    'view' => $this->render('blocks/serie/_viewing_season.html.twig', [
                        'viewing' => $newTab,
                        'season_number' => $tab['season_number'],
                        'episode_count' => $tab['episode_count'],
                        'season_completed' => $tab['season_completed'],
                        'globalIndex' => $globalIndex,
                    ]),
                ];
                $globalIndex += $tab['episode_count'];
            }
        }

        return $this->json([
            'blocks' => $blocks,
            'viewedEpisodes' => $viewed,
            'episodeText' => $translator->trans($viewed > 1 ? "viewed episodes" : "viewed episode"),
        ]);
    }

    public function getEpisodeViewings($user, $serie, $SeasonNumber, $episodeNumber, $allBefore = false): array
    {
        $array = [];
        /** @var SerieViewing $serieViewing */
        $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $user, 'serie' => $serie]);

        if ($allBefore) {
            for ($i = 1; $i <= $SeasonNumber; $i++) {
                /** @var SeasonViewing $seasonViewing */
                $seasonViewing = $this->seasonViewingRepository->findOneBy(['serie' => $serieViewing, 'seasonNumber' => $i]);
                $max = ($i == $SeasonNumber) ? $episodeNumber : $seasonViewing->getEpisodeCount();
                for ($j = 1; $j <= $max; $j++) {
                    /** @var EpisodeViewing $episodeViewing */
                    $episodeViewing = $this->episodeViewingRepository->findOneBy(['season' => $seasonViewing, 'episodeNumber' => $j]);
                    $array[] = $episodeViewing;
                }
            }
        } else {
            /** @var SeasonViewing $seasonViewing */
            $seasonViewing = $this->seasonViewingRepository->findOneBy(['serie' => $serieViewing, 'seasonNumber' => $SeasonNumber]);
            /** @var EpisodeViewing $episodeViewing */
            $episodeViewing = $this->episodeViewingRepository->findOneBy(['season' => $seasonViewing, 'episodeNumber' => $episodeNumber]);
            $array[] = $episodeViewing;
        }
        return $array;
    }

    public function mySerieIds(SerieRepository $serieRepository, User $user): array
    {
        $mySerieIds = $serieRepository->findMySerieIds($user->getId());
        $serieIds = [];
        foreach ($mySerieIds as $mySerieId) {
            $serieIds[] = $mySerieId['serieId'];
        }
        return $serieIds;
    }
}

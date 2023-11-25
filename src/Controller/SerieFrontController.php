<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Episode;
use App\Entity\EpisodeViewing;
use App\Entity\Favorite;
use App\Entity\Networks;
use App\Entity\Season;
use App\Entity\Serie;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\AlertRepository;
use App\Repository\EpisodeRepository;
use App\Repository\EpisodeViewingRepository;
use App\Repository\FavoriteRepository;
use App\Repository\NetworksRepository;
use App\Repository\SeasonRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
use App\Service\DateService;
use App\Service\ImageConfiguration;
use App\Service\QuoteService;
use App\Service\TMDBService;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/{_locale}/series', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieFrontController extends AbstractController
{
    const MY_SERIES = 'my_series';
    const POPULAR = 'popular';
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const LATEST = 'latest';
    const SEARCH = 'search';

    public function __construct(
        private readonly AlertRepository          $alertRepository,
        private readonly DateService              $dateService,
        private readonly EpisodeRepository $episodeRepository,
        private readonly EpisodeViewingRepository $episodeViewingRepository,
        private readonly FavoriteRepository       $favoriteRepository,
        private readonly ImageConfiguration       $imageConfiguration,
        private readonly NetworksRepository       $networkRepository,
        private readonly SeasonRepository  $seasonRepository,
        private readonly SerieController          $serieController,
        private readonly SerieRepository          $serieRepository,
        private readonly SerieViewingRepository   $serieViewingRepository,
        private readonly SettingsRepository       $settingsRepository,
        private readonly TMDBService              $TMDBService,
        private readonly TranslatorInterface      $translator,
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'app_series_new', methods: ['GET'])]
    public function new(Request $request): Response
    {
        $TMDBService = $this->TMDBService;
        $serieRepository = $this->serieRepository;
        $networkRepository = $this->networkRepository;
        $imageConfiguration = $this->imageConfiguration;
        $imgConfig = $imageConfiguration->getConfig();

        /** @var User $user */
        $user = $this->getUser();
        $from = $request->query->get('from', self::MY_SERIES);

        $value = $request->query->get("value");
        $query = $request->query->get("query");
        $year = $request->query->get("year");
        $page = $request->query->getInt('p', 1);
        $tv = ['name' => ''];
        $serieId = "";
        $status = "Ko";
        $response = "Not found";
        $serie = null;
        $card = null;
        $pagination = null;

        if (is_numeric($value)) {
            $serieId = $value;
        } else {
            if (preg_match("~(\d+)~", $value, $matches) == 1) {
                $serieId = $matches[0];
            }
        }
        if (strlen($serieId)) {
            $standing = $TMDBService->getTv($serieId, $request->getLocale());

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

                $serie->setBackdropPath($tv['backdrop_path']);
                $serie->setEpisodeDurations($this->collectEpisodeDurations($tv));
                if ($tv['first_air_date'])
                    $serie->setFirstDateAir($this->dateService->newDateImmutable($tv['first_air_date'], 'Europe/Paris', true));
                else {
                    $serie->setFirstDateAir(null);
                }
                $serie->setName($tv['name']);
                $serie->setNumberOfEpisodes($tv['number_of_episodes']);
                $serie->setNumberOfSeasons($tv['number_of_seasons']);
                $serie->setOriginalName($tv['original_name']);
                $serie->setOverview($tv['overview']);
                $serie->setPosterPath($tv['poster_path']);
                $serie->setSerieId($tv['id']);
                $serie->setStatus($tv['status']);
                $serie->setOriginCountry($tv['origin_country'] ?? []);

                foreach ($tv['networks'] as $network) {
                    $m2mNetwork = $networkRepository->findOneBy(['name' => $network['name']]);

                    if ($m2mNetwork == null) {
                        $m2mNetwork = new Networks();
                        $m2mNetwork->setLogoPath($network['logo_path']);
                        $m2mNetwork->setName($network['name']);
                        $m2mNetwork->setNetworkId($network['id']);
                        $m2mNetwork->setOriginCountry($network['origin_country']);
                        $networkRepository->save($m2mNetwork, true);
                    }
                    $serie->addNetwork($m2mNetwork);
                }
                $serie->addUser($user);
                $serieRepository->save($serie, true);

                $this->getSeasonsAndEpisodes($tv, $serie);

                $this->serieController->createSerieViewing($user, $tv, $serie);

                if ($tv['backdrop_path']) $this->serieController->addSerieBackdrop($serie, $tv['backdrop_path']);
                if ($tv['poster_path']) $this->serieController->addSeriePoster($serie, $tv['poster_path'], $imgConfig);
                if ($tv['backdrop_path'] || $tv['poster_path'])
                    $serieRepository->save($serie, true);
                /*
                 */
                if ($from === self::POPULAR || $from === self::TOP_RATED || $from === self::AIRING_TODAY || $from === self::ON_THE_AIR || $from === self::LATEST) {
                    $card = $this->render('blocks/series/_card-popular.html.twig', [
                        'serie' => $tv,
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($user),
                        'imageConfig' => $imgConfig,
                    ]);
                }

                if ($from === self::SEARCH) {
                    $card = $this->render('blocks/series/_card-search.html.twig', [
                        'serie' => $tv,
                        'query' => $query ?: "",
                        'year' => $year ?: "",
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($user),
                        'imageConfig' => $imgConfig,
                    ]);
                }
            }
        }

        return $this->json([
            'serie' => $tv['name'],
            'status' => $status,
            'response' => $response,
            'id' => $serieId ?: $value,
            'card' => $card,
            'userSerieId' => $serie?->getId(),
            'pagination' => $pagination,
        ]);
    }

    public function getSeasonsAndEpisodes($tvSeries, $series): void
    {
        $numberOfSeasons = $tvSeries['number_of_seasons'];

        for ($seasonNumber = 1; $seasonNumber <= $numberOfSeasons; $seasonNumber++) {
            $tvSeason = json_decode($this->TMDBService->getTvSeason($series->getSerieId(), $seasonNumber, "fr"), true);

            $season = $this->seasonRepository->findOneBy(['series' => $series, 'seasonNumber' => $seasonNumber]);

            if (!$season) {
                $season = new Season();
            }
            $season->set_Id($tvSeason['_id']);
            $season->setAirDate($this->dateService->newDateImmutable($tvSeason['air_date'], 'Europe/Paris'));
            $season->setName($tvSeason['name']);
            $season->setOverview($tvSeason['overview']);
            $season->setPosterPath($tvSeason['poster_path']);
            $season->setSeasonNumber($seasonNumber);
            $season->setSeries($series);
            $season->setTmdbId($tvSeason['id']);
            $this->seasonRepository->save($season, true);

            $tvEpisodes = $tvSeason['episodes'];
            $episodeCount = count($tvEpisodes);
            for ($i = 1; $i <= $episodeCount; $i++) {
                $tvEpisode = $tvEpisodes[$i - 1];
                $episode = $this->episodeRepository->findOneBy(['series' => $series, 'season' => $season, 'episodeNumber' => $i]);
                if (!$episode) {
                    $episode = new Episode();
                }
                $episode->setAirDate($this->dateService->newDateImmutable($tvEpisode['air_date'], 'Europe/Paris'));
                $episode->setEpisodeNumber($tvEpisode['episode_number']);
                $episode->setName($tvEpisode['name']);
                $episode->setOverview($tvEpisode['overview']);
                $episode->setRuntime($tvEpisode['runtime']);
                $episode->setSeason($season);
                $episode->setSeasonNumber($tvEpisode['season_number']);
                $episode->setSeries($series);
                $episode->setStillPath($tvEpisode['still_path']);
                $episode->setTmdbId($tvEpisode['id']);
                $this->episodeRepository->save($episode, $i === $episodeCount);
            }
        }
    }

    public function collectEpisodeDurations($tv): array
    {
        $tmdb = $this->TMDBService;
        $id = $tv['id'];
        $durations = [];
        $durations['episode_run_time'] = $tv['episode_run_time'];

        foreach ($tv['seasons'] as $season) {
            $seasonNumber = $season['season_number'];
            if ($seasonNumber == 0) {
                continue;
            }
            if (key_exists($seasonNumber, $durations) && count($durations[$seasonNumber]) == $season['episode_count']) {
                continue;
            }
            $durations[$seasonNumber] = [];
            $standing = $tmdb->getTvSeason($id, $seasonNumber, 'fr');
            $tmdbSeason = json_decode($standing, true);
            foreach ($tmdbSeason['episodes'] as $episode) {
                $durations[$seasonNumber][] = [$episode['episode_number'] => $episode['runtime']];
            }
        }
        return $durations;
    }

    #[Route('/duration', name: 'app_series_duration')]
    public function getViewedEpisodesDuration(Request $request): Response
    {
        $t0 = microtime(true);
        $duration = 0;
        $durations = [];
        $durationsUpdated = false;
        $serieCount = 0;
        $seasonCount = 0;
        $episodeCount = 0;
        $nullDurationCount = 0;
        $log = [];

        /** @var User $user */
        $user = $this->getUser();
        $serieId = $request->query->get('id');
        if ($serieId) {
            $serie = $this->serieRepository->findOneBy(['id' => $serieId]);
            $SerieViewings = $this->serieViewingRepository->findBy(['user' => $user, 'serie' => $serie]);
        } else {
            $SerieViewings = $this->serieViewingRepository->findBy(['user' => $user]);
        }

        foreach ($SerieViewings as $serieViewing) {
            $serie = $serieViewing->getSerie();
            $log[] = $serie->getName();
            $id = $serie->getId();
            $durations = $serie->getEpisodeDurations();
            $last = 0;
            $episodeRuntimeExists = key_exists('episode_run_time', $durations);
            if ($episodeRuntimeExists && count($durations['episode_run_time']) > 0) {
                $last = reset($durations['episode_run_time']);
            }
            $serieCount++;

            foreach ($serieViewing->getSeasons() as $seasonViewing) {
                $seasonNumber = $seasonViewing->getSeasonNumber();
                if ($seasonNumber == 0) {
                    continue;
                }
                $seasonCount++;
                foreach ($seasonViewing->getEpisodes() as $episodeViewing) {
                    if ($episodeViewing->getViewedAt() == null) {
                        continue;
                    }
                    $episodeCount++;
                    $episodeNumber = $episodeViewing->getEpisodeNumber();
                    if (key_exists($seasonNumber, $durations)) {
                        if (key_exists($episodeNumber - 1, $durations[$seasonNumber])) {
                            if (key_exists($episodeNumber, $durations[$seasonNumber][$episodeNumber - 1])) {
                                $minutes = $durations[$seasonNumber][$episodeNumber - 1][$episodeNumber];
                                if ($minutes == null) {
                                    $minutes = $this->episodeDuration($serie->getSerieId(), $seasonNumber, $episodeNumber);
                                    $durations[$seasonNumber][$episodeNumber - 1] = [$episodeNumber => $minutes];
                                    $durationsUpdated = true;
                                    $log[] = sprintf("%s : S%02dE%02d (%d) durée mise à jour (%d)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id, $minutes);
                                    if (!$episodeRuntimeExists) {
                                        $nullDurationCount++;
                                        $log[] = sprintf("%s : S%02dE%02d (%d - null)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id);
                                    }
                                } else {
                                    if (!$episodeRuntimeExists) {
                                        $last = $minutes;
                                    }
                                }
                                $duration += $minutes;
                            } else {
                                $log[] = sprintf("%s : S%02dE%02d (%d - episode)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id);
                                $duration += $last;
                            }
                        } else {
                            $minutes = $this->episodeDuration($serie->getSerieId(), $seasonNumber, $episodeNumber);
                            $durations[$seasonNumber][$episodeNumber - 1] = [$episodeNumber => $minutes];
                            $duration += $minutes;
                            $durationsUpdated = true;
                            $log[] = sprintf("%s : S%02dE%02d (%d): %d minutes\n", $serie->getName(), $seasonNumber, $episodeNumber, $id, $minutes);
                        }
                    } else {
                        $log[] = sprintf("%s : S%02dE%02d (%d - season)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id);
                    }
                }
            }
            if ($durationsUpdated) {
                $serie->setEpisodeDurations($durations);
                $this->serieRepository->save($serie, true);
            }
        }
        $t1 = microtime(true);

        return $this->render('blocks/series/_duration.html.twig', [
            'duration' => $duration,
            'durations' => $durations,
            'time' => sprintf("%.02f", $t1 - $t0),
            'log' => $log,
            'serieCount' => $serieCount,
            'seasonCount' => $seasonCount,
            'episodeCount' => $episodeCount,
            'nullDurationCount' => $nullDurationCount,
        ]);
    }

    public function episodeDuration($serieId, $seasonNumber, $episodeNumber): ?int
    {
        $episode = json_decode($this->TMDBService->getTvEpisode($serieId, $seasonNumber, $episodeNumber, 'fr'), true);
        return $episode['runtime'];
    }

    #[Route('/favorite/{userId}/{mediaId}/{fav}', name: 'app_series_toggle_favorite', methods: 'GET')]
    public function toggleFavorite(bool $fav, int $userId, int $mediaId): Response
    {
        if ($fav) {
            $favorite = new Favorite($userId, $mediaId, 'serie');
            $this->favoriteRepository->save($favorite, true);
            $message = $this->translator->trans("Successfully added to favorites");
            $class = 'added';
        } else {
            $favorite = $this->favoriteRepository->findOneBy(['userId' => $userId, 'mediaId' => $mediaId, 'type' => 'serie']);
            $this->favoriteRepository->remove($favorite, true);
            $message = $this->translator->trans("Successfully removed from favorites");
            $class = 'removed';
        }

        return $this->json(['message' => $message, 'class' => $class]);
    }

    #[Route('/time/shifted/{userId}/{serieId}/{shifted}', name: 'app_series_toggle_time_shifted', methods: 'GET')]
    public function toggleTimeShifted(bool $shifted, int $userId, int $serieId): Response
    {
        $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $userId, 'serie' => $serieId]);
        $serieViewing->setTimeShifted($shifted);
        $this->serieViewingRepository->save($serieViewing, true);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/overview/{id}', name: 'app_series_get_overview', methods: 'GET')]
    public function getOverview(Request $request, $id, TMDBService $service, TranslatorInterface $translator): Response
    {
        $type = $request->query->get("type");
        $content = null;

        $standing = match ($type) {
            "tv" => $service->getTv($id, $request->getLocale()),
            "movie" => $service->getMovie($id, $request->getLocale()),
            default => null,
        };

        if ($standing) {
            $content = json_decode($standing, true);
        }
        return $this->json([
            'overview' => $content ? $content['overview'] : "",
            'media_type' => $translator->trans($type),
        ]);
    }

    #[Route('/render/translation/fields', name: 'app_series_render_translation_fields', methods: ['GET'])]
    public function renderTranslationFields(Request $request): Response
    {
        $keywords = json_decode($request->query->get('k'), true);

        return $this->render('blocks/series/_translationField.html.twig', [
            'keywords' => $keywords
        ]);
    }

    #[Route('/render/translation/select', name: 'app_series_render_translation_select', methods: ['GET'])]
    public function renderTranslationSelect(Request $request): Response
    {
        return $this->render('blocks/series/_translationSelect.html.twig', [
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/render/translation/save', name: 'app_series_render_translation_save', methods: ['GET'])]
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

    #[Route('/quote', name: 'app_series_get_quote', methods: ['GET'])]
    public function getQuote(): Response
    {
        return $this->json([
            'quote' => (new QuoteService)->getRandomQuote(),
        ]);
    }

    #[Route('/settings/save', name: 'app_series_set_settings', methods: ['GET'])]
    public function setSeriesSettings(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();
        $content = json_decode($request->query->get("data"), true);

        $settings = $this->settingsRepository->findOneBy(["user" => $user, "name" => $content["name"]]);

        if ($settings == null) {
            $settings = new Settings();
            $settings->setUser($user);
            $settings->setName($content["name"]);
        }
        $settings->setData($content["data"]);
        $this->settingsRepository->save($settings, true);

        return $this->json($this->translator->trans("The settings have been saved"));
    }

    #[Route('/episode/vote/{id}/{vote}', name: 'app_episode_vote', methods: ['GET'])]
    public function episodeVote(EpisodeViewing $episodeViewing, int $vote): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $episodeViewing->setVote($vote);
        $this->episodeViewingRepository->save($episodeViewing, true);

        $serieViewing = $episodeViewing->getSeason()->getSerieViewing();
        $serieViewing->setModifiedAt($this->dateService->newDate('now', $user->getTimezone()));
        $this->serieViewingRepository->save($serieViewing, true);

        return $this->json(['voteValue' => $vote, 'episodeNumber' => $episodeViewing->getEpisodeNumber()]);
    }

    #[Route('/episode/view/{id}/{view}', name: 'app_episode_view', methods: ['GET'])]
    public function episodeView(Request $request, EpisodeViewing $episodeViewing, int $view): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($view == 0) {
            $date = $this->dateService->newDateImmutable('now', $user->getTimezone());
            $episodeViewing->setViewedAt($date);
            $episodeViewing->setNumberOfView($episodeViewing->getNumberOfView() + 1);
            $view = 1;
        } else {
            $episodeViewing->setViewedAt(null);
            $view = 0;
        }
        $this->episodeViewingRepository->save($episodeViewing, true);

        $serieViewing = $episodeViewing->getSeason()->getSerieViewing();
        $alert = $this->alertRepository->findOneBy(['user' => $this->getUser(), 'serieViewingId' => $serieViewing->getId()]);

        $modifiedAt = $this->dateService->newDate('now', $user->getTimezone());
        $serieViewing->setModifiedAt($modifiedAt);

        $this->serieController->setViewedEpisodeCount($serieViewing);
        $seasonCompleted = $this->serieController->viewingCompleted($serieViewing);
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
            $this->serieController->setNextEpisode($tv, $serieViewing);
            if ($alert) {
                $this->serieController->updateAlert($alert, $serieViewing);
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
        /** @var User $user */
        $user = $this->getUser();

        if ($networkId == -1) {
            $episodeViewing->setNetworkId(null);
            $episodeViewing->setNetworkType('other');
        } else {
            $episodeViewing->setNetworkId($networkId);
            $episodeViewing->setNetworkType('flatrate');
        }
        $this->episodeViewingRepository->save($episodeViewing, true);

        $serieViewing = $episodeViewing->getSeason()->getSerieViewing();
        $serieViewing->setModifiedAt($this->dateService->newDate('now', $user->getTimezone()));
        $this->serieViewingRepository->save($serieViewing, true);

        return $this->json([
            'result' => 'success',
            'networkId' => $networkId
        ]);
    }

    #[Route('/episode/view/device/{id}/{device}', name: 'app_episode_view_device', methods: ['GET'])]
    public function episodeViewDevice(EpisodeViewing $episodeViewing, string $device): Response
    {
        $episodeViewing->setDeviceType($device);
        $this->episodeViewingRepository->save($episodeViewing, true);

        return $this->json([
            'result' => 'success',
            'device' => $device
        ]);
    }

    #[Route('/settings/set/{settings}', name: 'app_set_settings', methods: ['GET'])]
    public function setSettings($settings): Response
    {
        $settings = json_decode($settings, true);
//        dump($settings);

        /** @var User $user */
        $user = $this->getUser();
        $settingsDB = $this->settingsRepository->findOneBy(['user' => $user, 'name' => 'settings']);
        $settingsDB->setData($settings);
        $this->settingsRepository->save($settingsDB, true);

        return $this->json([
            'result' => 'success',
            'settingsDB' => $settingsDB,
            'settings' => $settings
        ]);
    }

    #[Route('/upcoming/date', name: 'app_series_upcoming_date', methods: ['GET'])]
    public function serieUpcomingDate(Request $request): Response
    {
        $id = $request->query->getInt('id');
        $month = $request->query->get('month');
        $year = $request->query->get('year');

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
            $nextEpisodeToWatch = $this->serieController->getNextEpisodeToWatch($serieViewing, $locale);

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
        if (!$serie) {
            return $this->json(['success' => false, 'message' => 'Serie not found']);
        }

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
        $list = $this->serieController->getRegionProvider($imgConfig, 1, $language, $region);
        $block = '<img src="' . $list[$providerId]['logo_path'] . '" alt="' . $list[$providerId]['provider_name'] . '" title="' . $list[$providerId]['provider_name'] . '">';

        return $this->json(['success' => true, 'block' => $block]);
    }
}

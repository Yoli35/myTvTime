<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Networks;
use App\Entity\Serie;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\NetworksRepository;
use App\Repository\SerieRepository;
use App\Repository\SerieViewingRepository;
use App\Repository\SettingsRepository;
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

#[Route('/{_locale}/serie', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieFrontController extends AbstractController
{
    const MY_SERIES = 'my_series';
    const POPULAR = 'popular';
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const LATEST = 'latest';
    const SEARCH = 'search';

    public function __construct(private readonly SerieController        $serieController,
                                private readonly FavoriteRepository     $favoriteRepository,
                                private readonly TranslatorInterface    $translator,
                                private readonly TMDBService            $tmdbService,
                                private readonly SerieRepository        $serieRepository,
                                private readonly SerieViewingRepository $serieViewingRepository,
                                private readonly NetworksRepository     $networkRepository,
                                private readonly ImageConfiguration     $imageConfiguration)
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'app_serie_new', methods: ['GET'])]
    public function new(Request $request): Response
    {
        $tmdbService = $this->tmdbService;
        $serieRepository = $this->serieRepository;
        $networkRepository = $this->networkRepository;
        $imageConfiguration = $this->imageConfiguration;

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

                $serie->setBackdropPath($tv['backdrop_path']);
                $serie->setEpisodeDurations($this->collectEpisodeDurations($tv));
                $serie->setFirstDateAir(new DateTimeImmutable($tv['first_air_date'] . 'T00:00:00'));
                $serie->setName($tv['name']);
                $serie->setNumberOfEpisodes($tv['number_of_episodes']);
                $serie->setNumberOfSeasons($tv['number_of_seasons']);
                $serie->setOriginalName($tv['original_name']);
                $serie->setOverview($tv['overview']);
                $serie->setPosterPath($tv['poster_path']);
                $serie->setSerieId($tv['id']);
                $serie->setStatus($tv['status']);

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
                $this->serieController->createSerieViewing($user, $tv, $serie);
                /*
                 */
                if ($from === self::POPULAR || $from === self::TOP_RATED || $from === self::AIRING_TODAY || $from === self::ON_THE_AIR || $from === self::LATEST) {
                    $card = $this->render('blocks/serie/_card-popular.html.twig', [
                        'serie' => $tv,
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($user),
                        'imageConfig' => $imageConfiguration->getConfig()]);
                }

                if ($from === self::SEARCH) {
                    $card = $this->render('blocks/serie/_card-search.html.twig', [
                        'serie' => $tv,
                        'query' => $query ?: "",
                        'year' => $year ?: "",
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($user),
                        'imageConfig' => $imageConfiguration->getConfig()]);
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

    public function collectEpisodeDurations($tv): array
    {
        $tmdb = $this->tmdbService;
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

    #[Route('/duration', name: 'app_serie_duration')]
    public function getViewedEpisodesDuration(Request $request): Response
    {
        $t0 = microtime(true);
        $duration = 0;
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
                                    $minutes = $last;
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
                            $log[] = sprintf("%s : S%02dE%02d (%d - episode-1)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id);
                        }
                    } else {
                        $log[] = sprintf("%s : S%02dE%02d (%d - season)\n", $serie->getName(), $seasonNumber, $episodeNumber, $id);
                    }
                }
            }
        }
        $t1 = microtime(true);

        return $this->json([
            'duration' => $duration,
            'time' => sprintf("%.02f", $t1 - $t0),
            'log' => $log,
            'serieCount' => $serieCount,
            'seasonCount' => $seasonCount,
            'episodeCount' => $episodeCount,
            'nullDurationCount' => $nullDurationCount,
        ]);
    }

    #[Route('/favorite/{userId}/{mediaId}/{fav}', name: 'app_serie_toggle_favorite', methods: 'GET')]
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

//        $serie = $this->serieRepository->findOneBy(['']);
        return $this->json(['message' => $message, 'class' => $class]);
    }

    #[Route('/timeShifted/{userId}/{serieId}/{shifted}', name: 'app_serie_toggle_time_shifted', methods: 'GET')]
    public function toggleTimeShifted(bool $shifted, int $userId, int $serieId): Response
    {
        $serieViewing = $this->serieViewingRepository->findOneBy(['user' => $userId, 'serie' => $serieId]);
        $serieViewing->setTimeShifted($shifted);
        $this->serieViewingRepository->save($serieViewing, true);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/overview/{id}', name: 'app_serie_get_overview', methods: 'GET')]
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

    #[Route('/render/translation/fields', name: 'app_serie_render_translation_fields', methods: ['GET'])]
    public function renderTranslationFields(Request $request): Response
    {
        $keywords = json_decode($request->query->get('k'), true);

        return $this->render('blocks/serie/_translationField.html.twig', [
            'keywords' => $keywords
        ]);
    }

    #[Route('/render/translation/select', name: 'app_serie_render_translation_select', methods: ['GET'])]
    public function renderTranslationSelect(Request $request): Response
    {
        return $this->render('blocks/serie/_translationSelect.html.twig', [
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

    #[Route('/quote', name: 'app_serie_get_quote', methods: ['GET'])]
    public function getQuote(): Response
    {
        return $this->json([
            'quote' => (new QuoteService)->getRandomQuote(),
        ]);
    }

    #[Route('/settings/save', name: 'app_serie_set_settings', methods: ['GET'])]
    public function setSettings(Request $request, SettingsRepository $settingsRepository, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var User $user */
        $user = $this->getUser();
        $content = json_decode($request->query->get("data"), true);

        $settings = $settingsRepository->findOneBy(["user" => $user, "name" => $content["name"]]);

        if ($settings == null) {
            $settings = new Settings();
            $settings->setUser($user);
            $settings->setName($content["name"]);
        }
        $settings->setData($content["data"]);
        $settingsRepository->save($settings, true);

        return $this->json($translator->trans("The settings have been saved"));
    }
}

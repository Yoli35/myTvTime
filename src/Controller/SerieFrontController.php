<?php

namespace App\Controller;

use App\Entity\Network;
use App\Entity\Serie;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\EpisodeViewingRepository;
use App\Repository\NetworkRepository;
use App\Repository\SeasonViewingRepository;
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

#[Route('/{_locale}/serie/internal', requirements: ['_locale' => 'fr|en|de|es'])]
class SerieFrontController extends AbstractController
{
    const LINK_COUNT = 7;
    const PER_PAGE_ARRAY = [1 => 10, 2 => 20, 3 => 50, 4 => 100];
    const MY_SERIES = 'my_series';
    const POPULAR = 'popular';
    const TOP_RATED = 'top_rated';
    const AIRING_TODAY = 'airing_today';
    const ON_THE_AIR = 'on_the_air';
    const LATEST = 'latest';
    const SEARCH = 'search';

    private SerieViewingRepository $serieViewingRepository;
    private SeasonViewingRepository $seasonViewingRepository;
    private EpisodeViewingRepository $episodeViewingRepository;
    private SerieController $serieController;

    public function __construct(SerieController $serieController, SerieViewingRepository $serieViewingRepository, SeasonViewingRepository $seasonViewingRepository, EpisodeViewingRepository $episodeViewingRepository)
    {
        $this->serieController = $serieController;
        $this->serieViewingRepository = $serieViewingRepository;
        $this->seasonViewingRepository = $seasonViewingRepository;
        $this->episodeViewingRepository = $episodeViewingRepository;
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
        $query = $request->query->get("query");
        $year = $request->query->get("year");
        $page = $request->query->getInt('p', 1);
        $perPage = $request->query->getInt('pp', 20);
        $orderBy = $request->query->getAlpha('ob', 'firstDateAir');
        $order = $request->query->getAlpha('o', 'desc');
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
                // // dump($tv);

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

                $serie->setOriginalName($tv['original_name']);
                $serie->setStatus($tv['status']);

                foreach ($tv['networks'] as $network) {
                    $m2mNetwork = $networkRepository->findOneBy(['name' => $network['name']]);

                    if ($m2mNetwork == null) {
                        $m2mNetwork = new Network();
                        $m2mNetwork->setName($network['name']);
                        $m2mNetwork->setLogoPath($network['logo_path']);
                        $m2mNetwork->setOriginCountry($network['origin_country']);
                        $networkRepository->save($m2mNetwork, true);
                    }
                    $serie->addNetwork($m2mNetwork);
                }
                $serie->addUser($user);
                $serieRepository->save($serie, true);
                dump($serie);
                $this->serieController->createViewing($user, $tv, $serie);
                /*
                 */
                if ($from === self::POPULAR || $from === self::TOP_RATED || $from === self::AIRING_TODAY || $from === self::ON_THE_AIR || $from === self::LATEST) {
                    // dump("Not my series");
                    $card = $this->render('blocks/serie/card-popular.html.twig', [
                        'serie' => $tv,
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($serieRepository, $user),
                        'imageConfig' => $imageConfiguration->getConfig()]);
                }

                if ($from === self::SEARCH) {
                    // dump("Not my series");
                    $card = $this->render('blocks/serie/card-search.html.twig', [
                        'serie' => $tv,
                        'query' => $query ?: "",
                        'year' => $year ?: "",
                        'pages' => [
                            'page' => $page
                        ],
                        'from' => $from,
                        'serieIds' => $this->serieController->mySerieIds($serieRepository, $user),
                        'imageConfig' => $imageConfiguration->getConfig()]);
                }

                if ($from === self::MY_SERIES) {
                    // dump("My series");
//                    $card = $this->render('blocks/serie/card.html.twig', [
//                        'serie' => $serie,
//                        'pages' => [
//                            'page' => $page
//                        ],
//                        'from' => $from,
//                        'imageConfig' => $imageConfiguration->getConfig()]);

                    $totalResults = $serieRepository->count([]);
                    $pagination = $this->render('blocks/serie/pagination.html.twig', [
                        'pages' => [
                            'total_results' => $totalResults,
                            'page' => $page,
                            'per_page' => $perPage,
                            'link_count' => self::LINK_COUNT,
                            'paginator' => $this->serieController->paginator($totalResults, $page, $perPage, self::LINK_COUNT),
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
            'userSerieId' => $serie?->getId(),
            'pagination' => $pagination,
        ]);
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

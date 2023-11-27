<?php

namespace App\Controller;

use App\Entity\Contribution;
use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\User;
use App\Entity\Movie;
use App\Form\ContributionType;
use App\Form\MovieByNameType;
use App\Repository\ContributionRepository;
use App\Repository\FavoriteRepository;
use App\Repository\GenreRepository;
use App\Repository\MovieListRepository;
use App\Repository\RatingRepository;
use App\Repository\MovieRepository;
use App\Service\FileUploader;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;

//use Google\ApiCore\ValidationException;
//use Google\Cloud\Translate\V3\TranslationServiceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MovieController extends AbstractController
{
    public function __construct(
        private readonly ContributionRepository $contributionRepository,
        private readonly FavoriteRepository     $favoriteRepository,
        private readonly FormFactoryInterface   $formFactory,
        private readonly GenreRepository        $genreRepository,
        private readonly ImageConfiguration     $imageConfiguration,
        private readonly MovieListRepository    $movieListRepository,
        private readonly MovieRepository        $movieRepository,
        private readonly RatingRepository       $ratingRepository,
        private readonly TMDBService            $tmdbService,
        private readonly TranslatorInterface    $translator,
    )
    {
    }

    #[Route([
        'fr' => '/{_locale}/films/',
        'en' => '/{_locale}/movies/',
        'de' => '/{_locale}/filme/',
        'es' => '/{_locale}/peliculas/'
    ], name: 'app_movie_list', requirements: ['_locale' => 'fr|en|de|es', 'page' => 1, 'sort_by' => 'popularity.desc'])]
    public function index(Request $request): Response
    {
        $imageConfiguration = $this->imageConfiguration;
        $movieRepository = $this->movieRepository;
        $tmdbService = $this->tmdbService;

        /** @var User $user */
        $user = $this->getUser();
        $userMovieIds = [];
        if ($user) {
//            $userMovies = $movieRepository->findUserMovieIds($user->getId());
//            foreach ($userMovies as $userMovie) {
//                $userMovieIds[] = $userMovie['movie_db_id'];
//            }
            $userMovieIds = array_map(function ($userMovie) {
                return $userMovie['movie_db_id'];
            }, $movieRepository->findUserMovieIds($user->getId()));
        }

        $options = [
            'fr' => [
                'Popularité ↑ (du moins vers le plus)' => 'popularity.asc',
                'Popularité ↓ (du plus vers le moins)' => 'popularity.desc',
                'Date de sortie ↑' => 'release_date.asc',
                'Date de sortie ↓' => 'release_date.desc',
                'Recettes ↑' => 'revenue.asc',
                'Recettes ↓' => 'revenue.desc',
                'Première date de sortie ↑' => 'primary_release_date.asc',
                'Première date de sortie ↓' => 'primary_release_date.desc',
                'Titre original ↑' => 'original_title.asc',
                'Titre original ↓' => 'original_title.desc',
                'Moyenne des votes ↑' => 'vote_average.asc',
                'Moyenne des votes ↓' => 'vote_average.desc',
                'Nombre de votes ↑' => 'vote_count.asc',
                'Nombre de votes ↓' => 'vote_count.desc'
            ],
            'en' => [
                'Ascending Popularity' => 'popularity.asc',
                'Descending Popularity' => 'popularity.desc',
                'Ascending Release Date' => 'release_date.asc',
                'Descending Release Date' => 'release_date.desc',
                'Ascending Revenue' => 'revenue.asc',
                'Descending Revenue' => 'revenue.desc',
                'Ascending Primary Release Date' => 'primary_release_date.asc',
                'Descending Primary Release Date' => 'primary_release_date.desc',
                'Ascending Original Title' => 'original_title.asc',
                'Descending Original Title' => 'original_title.desc',
                'Ascending Vote Average' => 'vote_average.asc',
                'Descending Vote Average' => 'vote_average.desc',
                'Ascending Vote Count' => 'vote_count.asc',
                'Descending Vote Count' => 'vote_count.desc'
            ],
            'de' => [
                'Aufsteigende Popularität' => 'popularity.asc',
                'Absteigende Popularität' => 'popularity.desc',
                'Aufsteigendes Veröffentlichungsdatum' => 'release_date.asc',
                'Absteigendes Veröffentlichungsdatum' => 'release_date.desc',
                'Aufsteigend Einnahmen' => 'revenue.asc',
                'Absteigend Umsatz' => 'revenue.desc',
                'Aufsteigend Primäres Veröffentlichungsdatum' => 'primary_release_date.asc',
                'Absteigend Primäres Freigabedatum' => 'primary_release_date.desc',
                'Aufsteigend Originaltitel' => 'original_title.asc',
                'Absteigend Originaltitel' => 'original_title.desc',
                'Aufsteigend Vote Average' => 'vote_average.asc',
                'Absteigender Stimmendurchschnitt' => 'vote_average.desc',
                'Aufsteigende Stimmenzahl' => 'vote_count.asc',
                'Absteigende Stimmenzahl' => 'vote_count.desc'
            ],
            'es' => [
                'Popularidad ascendente' => 'popularity.asc',
                'Popularidad descendente' => 'popularity.desc',
                'Fecha de lanzamiento ascendente' => 'release_date.asc',
                'Fecha de lanzamiento descendente' => 'release_date.desc',
                'Ingresos ascendentes' => 'revenue.asc',
                'Descendente Ingresos' => 'revenue.desc',
                'Fecha de lanzamiento principal ascendente' => 'primary_release_date.asc',
                'Descendente Fecha de publicación primaria' => 'primary_release_date.desc',
                'Ascendente Título original' => 'original_title.asc',
                'Descendente Título original' => 'original_title.desc',
                'Promedio de votos ascendente' => 'vote_average.asc',
                'Media de votos descendente' => 'vote_average.desc',
                'Recuento de votos ascendente' => 'vote_count.asc',
                'Recuento de votos descendente' => 'vote_count.desc'
            ]
        ];
        $locale = $request->getLocale();
        $sortBy = $request->query->get('sort', 'popularity.desc');
        $sorts = [
            'sort_by' => $sortBy,
            'options' => $options[$locale],
        ];

        $page = $request->query->getInt('page', 1);
        $standing = $tmdbService->discoverMovies($page, $sortBy, $locale);
        $discovers = json_decode($standing, true);
        $imageConfig = $imageConfiguration->getConfig();

        $pages = [
            'page' => $discovers['page'],
            'total_pages' => $discovers['total_pages'],
            'total_results' => $discovers['total_results']
        ];

        // Certains films ne possèdent pas tous les champs …
        foreach ($discovers['results'] as &$discover) {
            if (!array_key_exists('release_date', $discover)) {
                $discover['release_date'] = "";
            }
        }

        return $this->render('movie/index.html.twig', [
            'discovers' => $discovers['results'],
            'userMovies' => $userMovieIds,
            'imageConfig' => $imageConfig,
            'pages' => $pages,
            'sorts' => $sorts,
            'locale' => $locale,
            'from' => 'app_movie_list',
        ]);
    }

    #[Route([
        'fr' => '/{_locale}/film/{id}',
        'en' => '/{_locale}/movie/{id}',
        'de' => '/{_locale}/filme/{id}',
        'es' => '/{_locale}/pelicula/{id}'
    ], name: 'app_movie', requirements: ['_locale' => 'fr|en|de|es'])]
    public function show(Request $request, $id): Response
    {
//        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $imageConfig = $this->imageConfiguration->getConfig();
        $from = $request->query->get('from');
        $fromParams = [];
        foreach ($request->query->all() as $param => $value) {
            // "$request->query->all()" => array:2 [▼
            //    "from" → "app_movie_list_show"
            //    "--id" → "15"
            //  ]
            // si le nom du paramètre commence par '--'
            if (str_starts_with($param, '--')) {
                $param = substr($param, 2);
                $fromParams[$param] = $value;
            }
        }
//        dump([
//            'params' => $request->query->all(),
//            'from' => $from,
//            'fromParams' => $fromParams,
//        ]);

        $locale = $request->getLocale();
        $standing = $this->tmdbService->getMovie($id, $locale, ['videos,images,credits,recommendations,watch/providers']);
        $movieDetail = json_decode($standing, true);
        $recommendations = $movieDetail['recommendations']['results'];
        $credits = $movieDetail['credits'];
        $cast = $credits['cast'];
        $sortedCrew = $this->sortCrew($credits['crew']);
        $videos = $movieDetail['videos']['results'];
        $images = $movieDetail['images'];
        $watchProviders = $movieDetail['watch/providers']['results'];

        $standing = $this->tmdbService->getCountries($locale, $user?->getCountry());
        $countries = json_decode($standing, true);
        $countriesByCode = [];
        foreach ($countries as $country) {
            $countriesByCode[$country['iso_3166_1']] = $country['native_name'];
        }
//        dump([
//            'watchProviders' => $watchProviders,
//            'countries' => $countries,
//            'countriesByCode' => $countriesByCode,
//            'from' => $from,
//        ]);

        $breadcrumb = $this->breadcrumb($from, $movieDetail, null, null, $fromParams);
        if ($before = $request->query->get('before')) {
            $breadcrumb = $this->breadcrumbBefore($breadcrumb, $before);
        }

        if ($watchProviders) {
            if ($user && $user->getCountry()) {
                $country = $user->getCountry();
            } else {
                $arr = ['fr' => 'FR', 'en' => 'US', 'de' => 'DE', 'es' => 'ES'];
                $country = $arr[$locale];
            }
            if (key_exists($country, $watchProviders)) {
                $watchProvidersTemp[0] = $watchProviders[$country];
                $watchProviders = $watchProvidersTemp;
            } else {
                if (count($watchProviders) <= 3) {
                    $watchProvidersTemp = [];
                    foreach ($watchProviders as $country => &$watchProvider) {
                        $watchProvider['native_name'] = $countriesByCode[$country];
                        $watchProvidersTemp[] = $watchProvider;
                    }
                    $watchProviders = $watchProvidersTemp;
                } else
                    $watchProviders = null;
            }
        }
//        dump([
//            'watchProviders' => $watchProviders,
//        ]);

        $standing = $this->tmdbService->getMovieReleaseDates($id);
        $releaseDates = json_decode($standing, true);
        $releaseDates = $this->getLocaleDates($user, $releaseDates['results'], $countries, $locale);

        $hasBeenSeen = false;//$this->hasBeenSeen($id, $userMovieRepository);
        $movieLists = [];
        $movieListIds = [];
        $userMovieId = 0;

        if ($user) {
            $movieLists = $this->movieListRepository->findBy(['user' => $user], ['title' => 'ASC']);
            $userMovie = $this->movieRepository->findOneBy(['movieDbId' => $movieDetail['id']]);
            if ($userMovie) {
                // On hydrate le film avec les données de l'API (mise à jour des données)
                $userMovie = $this->hydrateMovie($userMovie, $movieDetail);
                $this->movieRepository->save($userMovie, true);

                $hasBeenSeen = count($this->movieRepository->isInUserMovies($user->getId(), $userMovie->getId()));

                $userMovieId = $userMovie->getId();
                $movieListIds = array_map(function ($movieList) {
                    return $movieList->getId();
                }, $userMovie->getMovieLists()->toArray());
//                dump([
//                    'movieLists' => $movieLists,
//                    'movieListIds' => $movieListIds,
//                ]);
            }
        }

        if (!array_key_exists('release_date', $movieDetail)) {
            $movieDetail['release_date'] = "";
        }

        $ygg = str_replace(' ', '+', $movieDetail['title']);
        $ygg = str_replace('\'', '+', $ygg);

        $overviewWasEmpty = false;

        if (!$movieDetail['overview'] || !strlen($movieDetail['overview'])) {
            $overviewWasEmpty = true;
            $movie = $this->movieRepository->findOneBy(['movieDbId' => $id]);
            switch ($locale) {
                case 'fr':
                    if ($movie && $movie->getOverviewFr()) {
                        $movieDetail['overview'] = $movie->getOverviewFr();
                    }
                    break;
                case 'en':
                    if ($movie && $movie->getOverviewEn()) {
                        $movieDetail['overview'] = $movie->getOverviewEn();
                    }
                    break;
                case 'es':
                    if ($movie && $movie->getOverviewEs()) {
                        $movieDetail['overview'] = $movie->getOverviewEs();
                    }
                    break;
                case 'de':
                    if ($movie && $movie->getOverviewDe()) {
                        $movieDetail['overview'] = $movie->getOverviewDe();
                    }
                    break;
            }
        }

        $backdrops = $this->getMediaContributions($id, 'movie', 'backdrop', $imageConfig, $movieDetail);
        $backdropForm = $this->getNamedForm($id, 'backdropForm', 'app_contribution_movie_backdrop', 'POST');

        $posters = $this->getMediaContributions($id, 'movie', 'poster', $imageConfig, $movieDetail);
        $posterForm = $this->getNamedForm($id, 'posterForm', 'app_contribution_movie_poster', 'POST');

//        dump([
//            'backdrops' => $backdrops,
//            'posters' => $posters,
//            'movieDetail' => $movieDetail,
//        ]);

        return $this->render('movie/show.html.twig', [
            'movie' => $movieDetail,
            'backdropForm' => $backdropForm->createView(),
            'backdrops' => $backdrops,
            'posterForm' => $posterForm->createView(),
            'posters' => $posters,
            'from' => $from,
            'breadcrumb' => $breadcrumb,
            'overviewWasEmpty' => $overviewWasEmpty,
            'recommendations' => $recommendations,
            'dates' => $releaseDates,
            'watchProviders' => $watchProviders,
            'hasBeenSeen' => $hasBeenSeen,
            'cast' => $cast,
            'sortedCrew' => $sortedCrew,
            'userMovieId' => $userMovieId,
            'movieLists' => $movieLists,
            'movieListIds' => $movieListIds,
            'images' => $images,
            'videos' => $videos,
            'user' => $user,
            'ygg' => $ygg,
            'imageConfig' => $imageConfig,
            'locale' => $locale,
        ]);
    }

    #[Route('/movie/{id}/backdrop', name: 'app_contribution_movie_backdrop', methods: ['POST'])]
    public function movieBackdrop(Request $request, $id, FileUploader $fileUploader): Response
    {
        $form = $this->getNamedForm($id, 'backdropForm', 'app_contribution_movie_backdrop', 'POST');
        $form->handleRequest($request);
        $dropMedia = $form->get('dropMedia')->getData();
        $caption = $form->get('caption')->getData();

        if ($dropMedia) {
            $backdropFileName = $fileUploader->upload($dropMedia, 'movie_backdrop');
            $contribution = new Contribution();
            $contribution->setUser($this->getUser());
            $contribution->setType('backdrop');
            $contribution->setMediaId($id);
            $contribution->setMediaType('movie');
            $contribution->setPath($backdropFileName);
            $contribution->setCaption($caption);
            $this->contributionRepository->save($contribution, true);

            return $this->json(['success' => true, 'type' => 'backdrop', 'path' => '/images/movies/contributions/backdrops/' . $backdropFileName, 'caption' => $caption, 'id' => $contribution->getId()]);
        }
        return $this->json(['success' => false, 'error' => 'no file']);
    }

    #[Route('/movie/{id}/poster', name: 'app_contribution_movie_poster', methods: ['POST'])]
    public function moviePoster(Request $request, $id, FileUploader $fileUploader): Response
    {
        $form = $this->getNamedForm($id, 'posterForm', 'app_contribution_movie_poster', 'POST');
        $form->handleRequest($request);
        $dropMedia = $form->get('dropMedia')->getData();
        $caption = $form->get('caption')->getData();

        if ($dropMedia) {
            $posterFileName = $fileUploader->upload($dropMedia, 'movie_poster');
            $contribution = new Contribution();
            $contribution->setUser($this->getUser());
            $contribution->setType('poster');
            $contribution->setMediaId($id);
            $contribution->setMediaType('movie');
            $contribution->setPath($posterFileName);
            $contribution->setCaption($caption);
            $this->contributionRepository->save($contribution, true);

            return $this->json(['success' => true, 'type' => 'poster', 'path' => '/images/movies/contributions/posters/' . $posterFileName, 'caption' => $caption, 'id' => $contribution->getId()]);
        }
        return $this->json(['success' => false, 'error' => 'no file']);
    }

    public function getMediaContributions($id, $mediaType, $type, $imageConfig, &$movieDetail): array
    {
        $localUrl = '/images/' . $mediaType . 's/contributions/' . $type . 's/';
        $url = $imageConfig['url'] . $imageConfig[$type . '_sizes'][3];
        $arrayContributions = $this->contributionRepository->findBy(['mediaId' => $id, 'mediaType' => $mediaType, 'type' => $type]);

        $array = array_map(function ($contribution) use ($localUrl) {
            return ['path' => $localUrl . $contribution->getPath(), 'caption' => $contribution->getCaption(), 'id' => $contribution->getId()];
        }, $arrayContributions);

        $movieDetail[$type . '_caption'] = null;
        if (!$movieDetail[$type . '_path']) {
            if (count($arrayContributions)) {
                $itemContribution = $arrayContributions[0];
                $movieDetail[$type . '_path'] = $localUrl . $itemContribution->getPath();
                $movieDetail[$type . '_caption'] = $itemContribution->getCaption();
            }
        } else {
            $movieDetail[$type . '_path'] = $url . $movieDetail[$type . '_path'];
            $array = array_merge([['path' => $movieDetail[$type . '_path'], 'caption' => null, 'id' => null]], $array);
        }
        return $array;
    }

    public function getNamedForm($id, $name, $route, $method): FormInterface
    {
        return $this->formFactory->createNamed($name, ContributionType::class, null, [
            'action' => $this->generateUrl($route, ['id' => $id]),
            'method' => $method,
        ]);
    }

    #[Route([
        'fr' => '/{_locale}/film/collection/{mid}/{id}',
        'en' => '/{_locale}/movie/collection/{mid}/{id}',
        'de' => '/{_locale}/filme/sammlung/{mid}/{id}',
        'es' => '/{_locale}/pelicula/coleccion/{mid}/{id}'
    ], 'app_movie_collection', requirements: ['_locale' => 'fr|en|de|es'])]
    public function movieCollection(Request $request, $mid, $id): Response
    {
        $from = $request->query->get('from');
        $locale = $request->getLocale();
        $standing = $this->tmdbService->getMovieCollection($id, $locale);
        $collection = json_decode($standing, true);
        $standing = $this->tmdbService->getMovie($mid, $locale);
        $movie = json_decode($standing, true);

        $genresEntity = $this->genreRepository->findAll();
        $genres = [];
        foreach ($genresEntity as $genre) {
            $genres[$genre->getGenreId()] = $genre->getName();
        }
        foreach ($collection['parts'] as &$part) {
            $part['genres'] = [];
            foreach ($part['genre_ids'] as $genre_id) {
                $genre = ['id' => $genre_id, 'name' => $genres[$genre_id]];
                $part['genres'][] = $genre;
            }
        }

        $imageConfig = $this->imageConfiguration->getConfig();
        $breadcrumb = $this->breadcrumb($from, $movie, $collection);

        return $this->render('movie/collection.html.twig', [
            'movie' => $movie,
            'from' => $from,
            'breadcrumb' => $breadcrumb,
            'collection' => $collection,
            'userMovies' => $this->getUserMovieIds(),
            'genres' => $genres,
            'user' => $this->getUser(),
            'imageConfig' => $imageConfig,
            'locale' => $request->getLocale(),
        ]);

    }

    #[Route([
        'fr' => '/{_locale}/films/recherche/par/genre/{genres}/{page}',
        'en' => '/{_locale}/movies/by/genre/{genres}/{page}',
        'de' => '/{_locale}/filmen/nach/genre/{genres}/{page}',
        'es' => '/{_locale}/peliculas/por/genero/{genres}/{page}'
    ], name: 'app_movies_by_genre', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByGenres(Request $request, $page, $genres): Response
    {
        $locale = $request->getLocale();
        $standing = $this->tmdbService->moviesByGenres($page, $genres, $locale);
        $discovers = json_decode($standing, true);
        $standing = $this->tmdbService->getGenres($locale);
        $possibleGenres = json_decode($standing, true);

        $currentGenres = explode(',', $genres); // "Action,Adventure" => ['Action', 'Adventure']
        $imageConfig = $this->imageConfiguration->getConfig();

        return $this->render('movie/genre.html.twig', [
            'discovers' => $discovers,
            'userMovies' => $this->getUserMovieIds(),
            'genres' => $genres,
            'possible_genres' => $possibleGenres,
            'current_genres' => $currentGenres,
            'imageConfig' => $imageConfig,
            'dRoute' => 'app_movie',
            'from' => 'app_movies_by_genre',
            'user' => $this->getUser(),
            'locale' => $locale,
        ]);
    }

    #[Route([
        'fr' => '/{_locale}/films/recherche/par/date/{date}/{page}',
        'en' => '/{_locale}/movies/by/date/{date}/{page}',
        'de' => '/{_locale}/filmen/nach/datum/{date}/{page}',
        'es' => '/{_locale}/peliculas/por/fecha/{date}/{page}'
    ], name: 'app_movies_by_date', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByDate(Request $request, $page, $date): Response
    {
        $locale = $request->getLocale();
        $standing = $this->tmdbService->moviesByDate($page, $date, $locale);
        $discovers = json_decode($standing, true);
        $imageConfig = $this->imageConfiguration->getConfig();

        $now = intval(date("Y"));
        $years = [];
        for ($i = $now; $i >= 1874; $i--) {
            $years[] = $i;
        }

        return $this->render('movie/date.html.twig', [
            'discovers' => $discovers,
            'userMovies' => $this->getUserMovieIds(),
            'date' => $date,
            'years' => $years,
            'imageConfig' => $imageConfig,
            'user' => $this->getUser(),
            'dRoute' => 'app_movie',
            'from' => 'app_movies_by_date',
            'locale' => $locale,
        ]);
    }

    #[Route([
        'fr' => '/{_locale}/films/recherche/par/nom/{page}',
        'en' => '/{_locale}/movies/search/{page}',
        'de' => '/{_locale}/filmen/suche/{page}',
        'es' => '/{_locale}/peliculas/buscar/{page}'
    ], name: 'app_movies_search', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesSearch(Request $request, $page): Response
    {
        $locale = $request->getLocale();
        $discovers = ['results' => [], 'page' => 0, 'total_pages' => 0, 'total_results' => 0];
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $imageConfig = $this->imageConfiguration->getConfig();

        $form = $this->createForm(MovieByNameType::class, ['query' => $query, 'year' => $year]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $form->getData();
            $query = $result['query'];
            $year = $result['year'];
            // Si une nouvelle recherche est lancée, on retourne à la première page
            $page = 1;
        }
        if ($query && strlen($query)) {
            $standing = $this->tmdbService->moviesSearch($page, $query, $year, $locale);
            $discovers = json_decode($standing, true);
        }

        $action = $this->generateUrl('app_movies_search', ['query' => $query, 'year' => $year]);

        return $this->render('movie/search.html.twig', [
            'form' => $form->createView(),
            'action' => $action,
            'query' => $query,
            'year' => $year,
            'page' => $page,
            'discovers' => $discovers,
            'userMovies' => $this->getUserMovieIds(),
            'imageConfig' => $imageConfig,
            'user' => $this->getUser(),
            'dRoute' => 'app_movie',
            'from' => 'app_movies_search',
            'locale' => $locale,
        ]);
    }

    #[Route('/favorite/{userId}/{mediaId}/{fav}', name: 'app_movie_favorite_toggle', methods: 'GET')]
    public function toggleFavorite(bool $fav, int $userId, int $mediaId): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($fav) {
            $favorite = new Favorite($userId, $mediaId, 'movie');
            $this->favoriteRepository->save($favorite, true);
            $message = $this->translator->trans("Successfully added to favorites", [], null, $user->getPreferredLanguage());
            $class = 'added';
        } else {
            $favorite = $this->favoriteRepository->findOneBy(['userId' => $userId, 'mediaId' => $mediaId, 'type' => 'movie']);
            $this->favoriteRepository->remove($favorite, true);
            $message = $this->translator->trans("Successfully removed from favorites", [], null, $user->getPreferredLanguage());
            $class = 'removed';
        }

        return $this->json(['message' => $message, 'class' => $class]);
    }

    #[Route('/overview/save', name: 'app_movie_overview_save', methods: 'GET')]
    function saveOverview(Request $request): Response
    {
        $overview = $request->query->get('overview');
        $id = $request->query->getInt('id');

        $movie = $this->movieRepository->find($id);

        switch ($request->getLocale()) {
            case 'fr':
                $movie->setOverviewFr($overview);
                break;
            case 'en':
                $movie->setOverviewEn($overview);
                break;
            case 'de':
                $movie->setOverviewDe($overview);
                break;
            case 'es':
                $movie->setOverviewEs($overview);
                break;
        }

        $this->movieRepository->save($movie, true);

        return $this->json(['message' => 'ok', 'overview' => $overview]);
    }

    #[Route('/overview/delete', name: 'app_movie_overview_delete', methods: 'GET')]
    function deleteOverview(Request $request): Response
    {
        $id = $request->query->getInt('id');

        $movie = $this->movieRepository->find($id);

        switch ($request->getLocale()) {
            case 'fr':
                $movie->setOverviewFr(null);
                break;
            case 'en':
                $movie->setOverviewEn(null);
                break;
            case 'de':
                $movie->setOverviewDe(null);
                break;
            case 'es':
                $movie->setOverviewEs(null);
                break;
        }

        $this->movieRepository->save($movie, true);

        return $this->json(['message' => 'ok']);
    }

    #[Route('/movie/add', name: 'app_movie_add')]
    public function addMovieToUser(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $locale = $request->getLocale();

        $userMovie = $this->addMovie($user, $movieId, $locale);
        return $this->json(['title' => $userMovie->getTitle()]);
    }

    #[Route('/movie/remove', name: 'app_movie_remove')]
    public function removeMovieFromUser(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');

        $userMovie = $this->movieRepository->findOneBy(['movieDbId' => $movieId]);

        if ($userMovie) {
            $userMovie->removeUser($user);
            $this->movieRepository->save($userMovie, true);
        }

        return $this->json(['/movie/remove' => 'success']);
    }

    #[Route('/movie/set/rating', name: 'app_movie_set_rating')]
    public function setMovieRating(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $movie = $this->movieRepository->findOneBy(['movieDbId' => $movieId]);
        $vote = $request->query->get('rating');
        $result = "update";

        $rating = $this->ratingRepository->findOneBy(['user' => $user, 'movie' => $movie]);

        if (!$rating) {
            $rating = new Rating();
            $rating->setUser($user);
            $rating->setMovie($movie);
            $result = "create";
        }
        $rating->setValue($vote);
        $this->ratingRepository->add($rating, true);

        return $this->json(['result' => $result]);
    }

    #[Route('/movie/get/rating', name: 'app_movie_get_rating')]
    public function getMovieRating(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $movieId = $request->query->get('movie_db_id');
        $movie = $this->movieRepository->findOneBy(['movieDbId' => $movieId]);
        $rating = $this->ratingRepository->findOneBy(['user' => $user, 'movie' => $movie]);

        return $this->json(['rating' => $rating ? $rating->getValue() : 0]);
    }

    public function breadcrumb($from, $movie = null, $movieCollection = null, $movieCollectionItem = null, $fromParams = []): array
    {
//        dump(['from' => $from, 'movie' => $movie, 'movieCollection' => $movieCollection, 'movieCollectionItem' => $movieCollectionItem]);
        /* TODO: User movies search field: from => app_personal_movies */
        if ($from === "") {
            $from = 'app_home';
        }
        $from = $from ?? 'app_movie_list';
        $baseUrl = $this->generateUrl($from, $fromParams);
        $baseName = match ($from) {
            'app_home' => $this->translator->trans("Home"),
            'app_personal_movies' => $this->translator->trans("My Movies"),
            'app_movies_by_genre' => $this->translator->trans("Search by Genre"),
            'app_movies_by_date' => $this->translator->trans("Search by Date"),
            'app_movies_search' => $this->translator->trans("Search by Name"),
            'app_movie_list_show' => $this->translator->trans("Movie List") . ' “ ' . $this->movieListRepository->find($fromParams['id'])->getTitle() . ' ”',
            default => $this->translator->trans("Movies"),
        };
        $breadcrumb = [
            [
                'name' => $baseName,
                'url' => $baseUrl,
            ]
        ];

        if ($movie) {
            $breadcrumb[] = [
                'name' => $movie['title'],
                'url' => $this->generateUrl("app_movie", ['id' => $movie['id']]) . "?from=$from",
            ];
        }
        if ($movieCollection) {
            $breadcrumb[] = [
                'name' => $movieCollection['name'],
                'url' => $this->generateUrl("app_movie_collection", ['mid' => $movie['id'], 'id' => $movieCollection['id']]) . "?from=$from",
            ];
        }
        if ($movieCollectionItem) {
            $breadcrumb[] = [
                'name' => $movieCollectionItem['title'],
                'url' => $this->generateUrl("app_movie", ['id' => $movieCollectionItem['id']]) . "?from=$from",
            ];
        }

        return $breadcrumb;
    }

    public function breadcrumbBefore($breadcrumb, $before): array
    {
        return array_merge([[
            'name' => match ($before) {
                'app_movie_list_index' => $this->translator->trans('My movie lists'),
                default => $this->translator->trans('Home'),
            },
            'url' => $this->generateUrl($before),
        ]], $breadcrumb);
    }

    public function getLocaleDates(?User $user, $dates, $countries, $locale): array
    {
        if ($user?->getCountry()) {
            $locales = [$locale => [$user->getCountry()]];
        } else {
            $locales = [
                'fr' => ['BE', 'BF', 'BJ', 'CA', 'CD', 'CG', 'CH', 'CI', 'FR', 'GA', 'GN', 'LU', 'MC', 'ML', 'NE', 'SN', 'TG'],
                'en' => ['AU', 'CA', 'GB', 'IE', 'MT', 'NZ', 'SG', 'US'],
                'de' => ['AT', 'BE', 'CH', 'DE', 'LI', 'LU'],
                'es' => ['AR', 'CL', 'CR', 'CU', 'ES', 'HN', 'NI', 'PR', 'SV', 'VE']
            ];
        }
//        dump(['locales' => $locales, 'locale' => $locale, 'user' => $user, 'dates' => $dates, 'countries' => $countries]);

        $types = [1 => 'Premiere', 2 => 'Theatrical (limited)', 3 => 'Theatrical', 4 => 'Digital', 5 => 'Physical', 6 => 'TV'];
        $localeDates = [];
        $c = [];

        foreach ($countries as $country) {
            $c[$country['iso_3166_1']] = $country['english_name'];
        }

        foreach ($dates as $d) {
            if (in_array($d['iso_3166_1'], $locales[$locale])) {
                $d['country'] = $c[$d['iso_3166_1']];
                foreach ($d['release_dates'] as &$release_date) {
                    $release_date['type'] = $types[$release_date['type']];
                }
                $localeDates[] = $d;
            }
        }

        return $localeDates;
    }

    public function sortCrew($crew): array
    {
        $sortedCrewWithProfile = [];
        $sortedCrewWithoutProfile = [];

        foreach ($crew as $people) {
            $profil = $people['profile_path'];

            if ($profil) {
                $sortedCrewWithProfile = $this->collectPeopleJobs($people, $sortedCrewWithProfile);
            } else {
                $sortedCrewWithoutProfile = $this->collectPeopleJobs($people, $sortedCrewWithoutProfile);
            }
        }

        ksort($sortedCrewWithProfile);
        ksort($sortedCrewWithoutProfile);
        $sortedCrewWithProfile = $this->skipKeys($sortedCrewWithProfile);
        $sortedCrewWithoutProfile = $this->skipKeys($sortedCrewWithoutProfile);

        return array_merge($sortedCrewWithProfile, $sortedCrewWithoutProfile);
    }

    public function skipKeys($array): array
    {
        $newArray = [];
        foreach ($array as $key => $varrayValue) {
            foreach ($varrayValue as $k => $value) {
                $newArray[] = $value;
            }
        }
        return $newArray;
    }

    public function collectPeopleJobs($people, $sortedCrew): array
    {
        $id = $people['id'];
        $name = $people['name'];
        $job = $people['job'];
        $profil = $people['profile_path'];

        if (!array_key_exists($job, $sortedCrew)) {
            $sortedCrew[$job] = [];
        }
        $sortedCrew[$job][] = ['id' => $id, 'name' => $name, 'profile_path' => $profil, 'job' => $job];
        return $sortedCrew;
    }

    public function getUserMovieIds(): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $userMovieIds = [];
        if ($user) {
            $userMovies = $this->movieRepository->findUserMovieIds($user->getId());
            $userMovieIds = array_map(function ($userMovie) {
                return $userMovie['movie_db_id'];
            }, $userMovies);
        }
        return $userMovieIds;
    }

    public function addMovie($user, $movieId, $locale): Movie
    {
        $userMovie = $this->movieRepository->findOneBy(['movieDbId' => $movieId]);

        if (!$userMovie) {
            $standing = $this->tmdbService->getMovie($movieId, $locale);
            $movieDetail = json_decode($standing, true);

            $userMovie = new Movie();
            $this->hydrateMovie($userMovie, $movieDetail);
        }
        $userMovie->addUser($user);
        $this->movieRepository->save($userMovie, true);

        return $userMovie;
    }

    public function hydrateMovie($userMovie, $movieDetail)
    {
        $userMovie->setTitle($movieDetail['title']);
        $userMovie->setOriginalTitle($movieDetail['original_title']);
        $userMovie->setPosterPath($movieDetail['poster_path']);
        $userMovie->setReleaseDate($movieDetail['release_date']);
        $userMovie->setMovieDbId($movieDetail['id']);
        $userMovie->setRuntime($movieDetail['runtime']);
        return $userMovie;
    }
}

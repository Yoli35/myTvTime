<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Rating;
use App\Entity\User;
use App\Entity\Movie;
use App\Form\MovieByNameType;
use App\Repository\FavoriteRepository;
use App\Repository\GenreRepository;
use App\Repository\MovieCollectionRepository;
use App\Repository\RatingRepository;
use App\Repository\MovieRepository;
use App\Service\CallImdbService;
use App\Service\LogService;
use App\Service\TMDBService;
use App\Service\ImageConfiguration;

//use Google\ApiCore\ValidationException;
//use Google\Cloud\Translate\V3\TranslationServiceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class MovieController extends AbstractController
{
    public function __construct(
        private readonly FavoriteRepository        $favoriteRepository,
        private readonly GenreRepository           $genreRepository,
        private readonly ImageConfiguration        $imageConfiguration,
        private readonly LogService                $logService,
        private readonly MovieCollectionRepository $movieCollectionRepository,
        private readonly MovieRepository           $movieRepository,
        private readonly RatingRepository          $ratingRepository,
        private readonly TMDBService               $tmdbService,
        private readonly TranslatorInterface       $translator,
    )
    {
    }

    #[Route(['fr' => '/{_locale}/films/', 'en' => '/{_locale}/movies/', 'de' => '/{_locale}/filme/', 'es' => '/{_locale}/peliculas/'], name: 'app_movie_list', requirements: ['_locale' => 'fr|en|de|es', 'page' => 1, 'sort_by' => 'popularity.desc'])]
    public function index(Request $request): Response
    {
        $imageConfiguration = $this->imageConfiguration;
        $movieRepository = $this->movieRepository;
        $tmdbService = $this->tmdbService;


        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $userMovieIds = [];
        if ($user) {
            $userMovies = $movieRepository->findUserMovieIds($user->getId());
            foreach ($userMovies as $userMovie) {
                $userMovieIds[] = $userMovie['movie_db_id'];
            }
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
        ]);
    }

    #[Route(['fr' => '/{_locale}/film/{id}', 'en' => '/{_locale}/movie/{id}', 'de' => '/{_locale}/filme/{id}', 'es' => '/{_locale}/pelicula/{id}'], name: 'app_movie', requirements: ['_locale' => 'fr|en|de|es'])]
    public function show(Request $request, $id): Response
    {
        $this->logService->log($request, $this->getUser());
        /** @var User $user */
        $user = $this->getUser();

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

        $standing = $this->tmdbService->getCountries();
        $countries = json_decode($standing, true);

        if (key_exists('results', $watchProviders)) {
            $watchProviders = $watchProviders['results'];
            if (key_exists(strtoupper($locale), $watchProviders)) {
                $watchProviders = $watchProviders[strtoupper($locale)];
            } else {
                $watchProviders = null;
            }
        }

        $standing = $this->tmdbService->getMovieReleaseDates($id);
        $releaseDates = json_decode($standing, true);
        $releaseDates = $this->getLocaleDates($releaseDates['results'], $countries, $locale);

        $imageConfig = $this->imageConfiguration->getConfig();

        $hasBeenSeen = false;//$this->hasBeenSeen($id, $userMovieRepository);
        $collections = [];
        $movieCollectionIds = [];
        $userMovieId = 0;

        if ($user) {
            $collections = $this->movieCollectionRepository->findBy(['user' => $user]);
            $userMovie = $this->movieRepository->findOneBy(['movieDbId' => $movieDetail['id']]);
            if ($userMovie) {
                $hasBeenSeen = count($this->movieRepository->isInUserMovies($user->getId(), $userMovie->getId()));

                $userMovieId = $userMovie->getId();
                $movieCollections = $userMovie->getMovieCollections();
                foreach ($movieCollections as $movieCollection) {
                    $movieCollectionIds[] = $movieCollection->getId();
                }
            }
        }


        if (!array_key_exists('release_date', $movieDetail)) {
            $movieDetail['release_date'] = "";
        }

        $ygg = str_replace(' ', '+', $movieDetail['title']);
        $ygg = str_replace('\'', '+', $ygg);

        if (!$movieDetail['overview'] || !strlen($movieDetail['overview'])) {
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
        dump($movieDetail);

        return $this->render('movie/show.html.twig', [
            'movie' => $movieDetail,
            'recommendations' => $recommendations,
            'dates' => $releaseDates,
            'watchProviders' => $watchProviders,
            'hasBeenSeen' => $hasBeenSeen,
            'cast' => $cast,
            'sortedCrew' => $sortedCrew,
            'userMovieId' => $userMovieId,
            'collections' => $collections,
            'movieCollection' => $movieCollectionIds,
            'images' => $images,
            'videos' => $videos,
            'user' => $user,
            'ygg' => $ygg,
            'imageConfig' => $imageConfig,
            'locale' => $locale,]);
    }

    public function getLocaleDates($dates, $countries, $locale): array
    {
        $locales = [
            'fr' => ['BE', 'BF', 'BJ', 'CA', 'CD', 'CG', 'CH', 'CI', 'FR', 'GA', 'GN', 'LU', 'MC', 'ML', 'NE', 'SN', 'TG'],
            'en' => ['AU', 'CA', 'GB', 'IE', 'MT', 'NZ', 'SG', 'US'],
            'de' => ['AT', 'BE', 'CH', 'DE', 'LI', 'LU'],
            'es' => ['AR', 'CL', 'CR', 'CU', 'ES', 'HN', 'NI', 'PR', 'SV', 'VE']
        ];

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
//                if (!array_key_exists($job, $sortedCrewWithProfile)) {
//                    $sortedCrewWithProfile[$job] = [];
//                }
//                if ($this->peopleInSortedCrew($people, $sortedCrewWithProfile[$job])) {
//                    $sortedCrewWithProfile[$job][$name]['job'] .= ', ' . $job;
//                } else {
//                    $sortedCrewWithProfile[$job][$name]['id'] = $id;
//                    $sortedCrewWithProfile[$job][$name]['profile_path'] = $profil;
//                    $sortedCrewWithProfile[$job][$name]['job'] = $job;
//                }
                $sortedCrewWithProfile = $this->collectPeopleJobs($people, $sortedCrewWithProfile);
            } else {
//                if ($this->peopleInSortedCrew($people, $sortedCrewWithoutProfile)) {
//                    $sortedCrewWithoutProfile[$people['name']]['job'] .= ', ' . $people['job'];
//                } else {
//                    $sortedCrewWithoutProfile[$people['name']]['id'] = $people['id'];
//                    $sortedCrewWithoutProfile[$people['name']]['profile_path'] = $people['profile_path'];
//                    $sortedCrewWithoutProfile[$people['name']]['job'] = $people['job'];
//                }
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

    function peopleInSortedCrew($p, $s): bool
    {
        foreach ($s as $key => $value) {
            if ($key == $p['name']) {
                return true;
            }
        }
        return false;
    }

    #[Route('/favorite/{userId}/{mediaId}/{fav}', name: 'app_movie_toggle_favorite', methods: 'GET')]
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

//        $serie = $this->serieRepository->findOneBy(['']);
        return $this->json(['message' => $message, 'class' => $class]);
    }

    #[Route(['fr' => '/{_locale}/film/collection/{mid}/{id}', 'en' => '/{_locale}/movie/collection/{mid}/{id}', 'de' => '/{_locale}/filme/sammlung/{mid}/{id}', 'es' => '/{_locale}/pelicula/coleccion/{mid}/{id}'], 'app_movie_collection', requirements: ['_locale' => 'fr|en|de|es'])]
    public function movieCollection(Request $request, $mid, $id): Response
    {
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

        return $this->render('movie/collection.html.twig', [
            'movie' => $movie,
            'collection' => $collection,
            'userMovies' => $this->getUserMovieIds(),
            'genres' => $genres,
            'user' => $this->getUser(),
            'imageConfig' => $imageConfig,
            'locale' => $request->getLocale(),
        ]);

    }

    #[Route(['fr' => '/{_locale}/films/recherche/par/genre/{genres}/{page}', 'en' => '/{_locale}/movies/by/genre/{genres}/{page}', 'de' => '/{_locale}/filmen/nach/genre/{genres}/{page}', 'es' => '/{_locale}/peliculas/por/genero/{genres}/{page}'], name: 'app_movies_by_genre', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByGenres(Request $request, $page, $genres): Response
    {
        $this->logService->log($request, $this->getUser());
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
            'user' => $this->getUser(),
            'locale' => $locale,
        ]);
    }

    #[Route(['fr' => '/{_locale}/films/recherche/par/date/{date}/{page}', 'en' => '/{_locale}/movies/by/date/{date}/{page}', 'de' => '/{_locale}/filmen/nach/datum/{date}/{page}', 'es' => '/{_locale}/peliculas/por/fecha/{date}/{page}'], name: 'app_movies_by_date', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesByDate(Request $request, $page, $date): Response
    {
        $this->logService->log($request, $this->getUser());
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
            'locale' => $locale,
        ]);
    }

    #[Route(['fr' => '/{_locale}/films/recherche/par/nom/{page}', 'en' => '/{_locale}/movies/search/{page}', 'de' => '/{_locale}/filmen/suche/{page}', 'es' => '/{_locale}/peliculas/buscar/{page}'], name: 'app_movies_search', requirements: ['_locale' => 'fr|en|de|es'], defaults: ['page' => 1])]
    public function moviesSearch(Request $request, $page): Response
    {
        $this->logService->log($request, $this->getUser());
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
            'locale' => $locale,
        ]);
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

    #[Route('/movie/collection/toggle', name: 'app_movie_collection_toggle')]
    public function toggleMovieToCollection(Request $request): Response
    {
        $collectionId = $request->query->getInt("c");
        $action = $request->query->get("a");
        $movieId = $request->query->getInt("m");

        $collection = $this->movieCollectionRepository->find($collectionId);
        $movie = $this->movieRepository->findOneBy(["movieDbId" => $movieId]);

        if ($action == "a") $collection->addMovie($movie);
        if ($action == "r") $collection->removeMovie($movie);
        $this->movieCollectionRepository->add($collection, true);

        $message = "The movie « movie_name » has been " . ($action == "a" ? "added to" : "removed from") . " your collection « collection_name ».";
        $message = $this->translator->trans($message, ["movie_name" => $movie->getTitle(), "collection_name" => $collection->getTitle()], "messages");
        return $this->json(["message" => $message]);
    }

    #[Route('/{_locale}/profile', name: 'profile_infos', requirements: ['_locale' => 'fr|en|de|es'], methods: "GET|POST")]
    public function getPersonInfos(Request $request, TMDBService $callTmdbService): JsonResponse
    {
        $id = $request->query->get('id');
        $locale = $request->query->get('locale');
        $standing = $callTmdbService->getPerson($id, $locale);
        $person = json_decode($standing, true);
        $department = ['fr' => ['Acting' => ['Acteur', 'Actrice', 'Acteur'], 'ADR Mixer' => ['Mixeur ADR (post-synchro)', 'Mixer ADR (post-synchro)', 'Mixer ADR (post-synchro)'], 'Art Direction' => ['Direction artistique', 'Direction artistique', 'Direction artistique'], 'Assistant Director' => ['Assistant réalisateur', 'Assistante réalisatrice', 'Assistant réalisateur'], 'Casting' => ['Distribution', 'Distribution', 'Distribution'], 'Costume Design' => ['Créateur de costumes', 'Créatrice de costumes', 'Créateur de costumes'], 'Costume Supervisor' => ['Superviseur des costumes', 'Superviseuse des costumes', 'Superviseur des costumes'], 'Director of Photography' => ['Directeur de la photographie', 'Directrice de la photographie', 'Directeur de la photographie'], 'Editing' => ['Édition', 'Édition', 'Édition'], 'Editor' => ['Éditeur', 'Éditrice', 'Éditeur'], 'Executive Producer' => ['Producteur délégué', 'Productrice déléguée', 'Producteur délégué'], 'Foley Artist' => ['Bruiteur', 'Bruiteuse', 'Bruiteur'], 'Line producer' => ['Producteur exécutif', 'Productrice exécutive', 'Producteur exécutif'], 'Makeup Artist' => ['Maquilleur', 'Maquilleuse', 'Maquilleur'], 'Music Supervisor' => ['Superviseur musical', 'Superviseuse musical', 'Superviseur musical'], 'Original Music Composer' => ['Compositeur de musique originale', 'Compositrice de musique originale', 'Compositeur de musique originale'], 'Producer' => ['Producteur', 'Productrice', 'Producteur'], 'Production Design' => ['Conception de production', 'Conception de production', 'Conception de production'], 'Screenplay' => ['Scénario', 'Scénario', 'Scénario'], 'Screenstory' => ['Scénario', 'Scénario', 'Scénario'], 'Set Decoration' => ['Décorateur', 'Décoratrice', 'Décorateur'], 'Set Designer' => ['Scénographe', 'Scénographe', 'Scénographe'], 'Set Dresser' => ['Habilleur', 'Habilleuse', 'Habilleur'], 'Set Manager' => ['Régisseur', 'Régisseuse', 'Régisseur'], 'Sound' => ['Son', 'Son', 'Son'], 'Sound Effects Editor' => ['Éditeur d\'effets sonores', 'Éditeur d\'effets sonores', 'Éditeur d\'effets sonores'], 'Sound Mixer' => ['Ingénieur du son', 'Ingénieure du son', 'Ingénieur du son'], 'Sound Re-Recording Mixer' => ['Mixeur son', 'Mixeuse son', 'Mixeur son'], 'Still Photographer' => ['Photographe de plateau', 'Photographe de plateau', 'Photographe de plateau'], 'Stunt' => ['Cascadeur', 'Cascadeuse', 'Cascadeur'], 'Supervising Art Director' => ['Directeur artistique superviseur', 'Directrice artistique superviseure', 'Directeur artistique superviseur'], 'Supervising Sound Editor' => ['Supervision du montage son', 'Supervision du montage son', 'Supervision du montage son'], 'VFX Artist' => ['Artiste d\'effets visuels', 'Artiste d\'effets visuels', 'Artiste d\'effets visuels'], 'Visual Effects' => ['Effets visuels', 'Effets visuels', 'Effets visuels'], 'Visual Effects Producer' => ['Producteur Effets visuels', 'Producteur Effets visuels', 'Producteur Effets visuels'], 'Visual Effects Supervisor' => ['Superviseur Effets visuels', 'Superviseuse Effets visuels', 'Superviseur Effets visuels'], 'Writing' => ['Écriture', 'Écriture', 'Écriture']], 'de' => ['Acting' => ['Schauspieler', 'Schauspielerin', 'Schauspieler'], 'ADR Mixer' => ['ADR-Mix (post-synchro)', 'ADR-Mix (post-synchro)', 'ADR-Mix (post-synchro)'], 'Art Direction' => ['Künstlerische Leitung ', 'Künstlerische Leitung ', 'Künstlerische Leitung '], 'Assistant Director' => ['Regieassistent ', 'Regieassistentin ', 'Regieassistent '], 'Casting' => ['Casting', 'Casting', 'Casting'], 'Costume Design' => ['Kostümbildner', 'Kostümbildnerin', 'Kostümbildner'], 'Costume Supervisor' => ['Supervisor für Kostüme', 'Supervisorin für Kostüme', 'Supervisor für Kostüme'], 'Director of Photography' => ['Direktor für Fotografie', 'Direktorin für Fotografie', 'Direktor für Fotografie'], 'Editing' => ['', '', ''], 'Editor' => ['', '', ''], 'Executive Producer' => ['', '', ''], 'Foley Artist' => ['', '', ''], 'Makeup Artist' => ['', '', ''], 'Music Supervisor' => ['', '', ''], 'Original Music Composer' => ['', '', ''], 'Producer' => ['', '', ''], 'Production Design' => ['', '', ''], 'Screenplay' => ['', '', ''], 'Screenstory' => ['', '', ''], 'Set Decoration' => ['', '', ''], 'Set Designer' => ['', '', ''], 'Set Dresser' => ['', '', ''], 'Sound Effects Editor' => ['', '', ''], 'Sound Mixer' => ['', '', ''], 'Sound Re-Recording Mixer' => ['', '', ''], 'Still Photographer' => ['', '', ''], 'Supervising Art Director' => ['', '', ''], 'Supervising Sound Editor' => ['', '', ''], 'VFX Artist' => ['', '', ''], 'Visual Effects' => ['', '', ''], 'Visual Effects Producer' => ['', '', ''], 'Visual Effects Supervisor' => ['', '', ''],], 'es' => ['Acting' => ['Actor', 'Actress', 'Actor'], 'ADR Mixer' => ['Mezcla ADR (post-sincronización)', 'Mezcla ADR (post-sincronización)', 'Mezcla ADR (post-sincronización)'], 'Art Direction' => ['Dirección artística', 'Dirección artística', 'Dirección artística'], 'Assistant Director' => ['Asistente de dirección', 'Asistente de dirección', 'Asistente de dirección'], 'Casting' => ['Casting', 'Casting', 'Casting'], 'Costume Design' => ['Diseñador de vestuario', 'Diseñadora de vestuario', 'Diseñador de vestuario'], 'Costume Supervisor' => ['Supervisor de vestuario', 'Supervisora de vestuario', 'Supervisor de vestuario'], 'Director of Photography' => ['Director de fotografía', 'Director de fotografía', 'Director de fotografía'], 'Editing' => ['', '', ''], 'Editor' => ['', '', ''], 'Executive Producer' => ['', '', ''], 'Foley Artist' => ['', '', ''], 'Makeup Artist' => ['', '', ''], 'Music Supervisor' => ['', '', ''], 'Original Music Composer' => ['', '', ''], 'Producer' => ['', '', ''], 'Production Design' => ['', '', ''], 'Screenplay' => ['', '', ''], 'Screenstory' => ['', '', ''], 'Set Decoration' => ['', '', ''], 'Set Designer' => ['', '', ''], 'Set Dresser' => ['', '', ''], 'Sound Effects Editor' => ['', '', ''], 'Sound Mixer' => ['', '', ''], 'Sound Re-Recording Mixer' => ['', '', ''], 'Still Photographer' => ['', '', ''], 'Supervising Art Director' => ['', '', ''], 'Supervising Sound Editor' => ['', '', ''], 'VFX Artist' => ['', '', ''], 'Visual Effects' => ['', '', ''], 'Visual Effects Producer' => ['', '', ''], 'Visual Effects Supervisor' => ['', '', ''],],];

        return $this->json(['success' => true, 'person' => $person, 'department' => $department, 'locale' => $locale,]);
    }

//    #[Route('/{_locale}/imdb', name: 'imdb_infos', requirements: ['_locale' => 'fr|en|de|es'], methods: "GET|POST")]
//    public function getPersonInfosOnIMDB(Request $request): JsonResponse
//    {
//        $name = $request->query->get('name');
//        $standing = $this->tmdbService->searchName($name);
//        $search = json_decode($standing, true);
//        $result = $search['results'][0];
//        $namePart = explode(" ", $name);
//
//        if (!strcmp($result['title'], $name) || !strcmp($result['title'], $namePart[1] . " " . $namePart[0])) {
//            $locale = $request->query->get('locale');
//            $standing = $this->tmdbService->getPerson($result['id'], $locale);
//            $person = json_decode($standing, true);
////            $summary = $translator->trans($person['summary']);
//
//            /*
//             * composer require "google/cloud-translate"
//             */
////            if ($locale !== 'en') {
////                $config = [
////                    'credentials' => [
////                        "type" => "service_account",
////                        "project_id" => "mytvtime-349019",
////                        "private_key_id" => "001b2f815d020608bcf09f3278e808fa0c52a6b7",
////                        "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCyXis5yVetkdre\niZqN7yrzy0kIydA4G/g9Wyh+b6VpOEz2kFjG5tIcibsEh8TP1mHPn0N95zovYv9S\n4bR3xfz1TJq9rxiVDgBLiKqQj/r8quLup0Uows3NohxtommAx2MybUrd+hngbzFD\nom+ELBty1TW3bZOz5kTpwdLKfrS5BgPdR01bJCPFn4STcc9gxGJAKXHDBF1+nbTk\n6c7vWBvKJUR8mxY20FfNEVeCoNAfBfyne2ZqOO5G8LOGFoYSjx0hNib84eB1cGCg\ncV+Ue/cpxyd3mn2v4maScs0CdAXhEAUKlQLMgCrnBUInu7lb4rfO4WJCvcg4Lr1+\nCIp62cK1AgMBAAECggEABj1GDNDuuLsb6WHt3p4ppfqL9Ps+ReAwmFDag0W7hwk5\no/xbpqWHXwkwWhG3wD9zD3S2Qy61+ddgMBGGIxRxa1FBLnZ0CS7CsuG2ebUXpgQC\nSS/fuvPJiDJuBSXDxAX1gduR3V70zcWF9yQ0+24hjaxIo0B5hLb+3SBzE7NH9hrh\nTmA3kyenwIrrzu/n+sM/edQZIj0r3Irhg3oO48UJS7HrDWwssTfHhZoE89oMNZBy\nqthl3nYkjcHtC1PuYLeg+gLyoVucoGE71zqACvbD9RjCHnlUHbgO6Q+DnKjwmLRj\nSC6qwUp/ZjxLFIYGuOjKj1nqQaU+RYdcJ/zJmIbGFwKBgQDbD7w8QWYUCo31uXE+\nf5tR8/UBV7bphcBuMGb2EJEA0ifT5Se0ePnT8PW0EtZf/TYEG0wxKIcFZVIVaeFp\nbh1fpj13rwwo+EN1n5EAbMK+A+AiLeJR8shwKirooddEuVVKm7hDH/Q2i4IoaZCO\naxBurr6WwX3HycEcY+RDQYcwRwKBgQDQcc4SyjVyCGExvGLX8ArxPsvdpixxmmvt\nKbL9vXP1+wl47b6+xf4vuxr2XWvZFoQtjrCNUqrRKbQxF+k1zpkxfP1qM/TfvxNS\nPsIw32I+BWH/2JrnVh+kpxpP5Quc2MgS+nQfUqAW5JBMSxUV9EgZbVuCsLWKwhvs\nU++6mSYPIwKBgCRiP6xuXEr12dA3RbTQsvZwo3/elrXAjk5+4Yr7A2p0fUL3a5nR\nAgWOnvCStGJrBv61nfkINyzRQEnoNRUywdQyI0FupIFlgqbVotrENbAjqqVio5Vi\n0qG2jzvmLX/vnFfw9zDG7OPmVe7qYaUV6TvI8ETPzFlTjCxv9uioyJBfAoGAHn7H\n20/iCdDYB2K8Q0NHFoxNXxwUnHovF/9lxGGXOYGEnUCLC3YD/g+tniWExbnZlKCv\ni71waDFlv1j0MX8MQoU6vfLj/GgD96Be4K+Nu+0lrTyPTRD4iCo6Wz3zOPsuKjii\nDIMWEMNXqRHC//dBJRcusCwSIz7KvwR4qiAFxWkCgYEAsCRAxJrGJMrh41nGLink\nGBP8NsWxysJi2ObeKmWWGnu7Gr7Vc7TYNVAsYBIoRHEhhUrSxz+VgJnVDPW7aC5p\nncR3Q7gHMd91wRJD3muP0ocDkPrZXiqdK9oiEbhU33KJGR9OD1Dvpcxvvhhyfgi5\npB++X6dH68Y7UIC8hM2i7GY=\n-----END PRIVATE KEY-----\n",
////                        "client_email" => "translate@mytvtime-349019.iam.gserviceaccount.com",
////                        "client_id" => "106684530697242476361",
////                        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
////                        "token_uri" => "https://oauth2.googleapis.com/token",
////                        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
////                        "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/translate%40mytvtime-349019.iam.gserviceaccount.com"
////                    ]
////                ];
////
////                $person['translated'] = '';
////                $translationClient = new TranslationServiceClient($config);
////                $content = [$summary];
////                $targetLanguage = $locale;
////                $response = $translationClient->translateText($content, $targetLanguage, TranslationServiceClient::locationName('mytvtime-349019', 'global'));
////
////                foreach ($response->getTranslations() as $key => $translation) {
////                    $person['translated'] .= $translation->getTranslatedText();
////                }
////            } else {
//            $person['translated'] = '';
////            }
//            $success = true;
//        } else {
//            $person = null;
//            $success = false;
//        }
//
//        return $this->json(['success' => $success, 'person' => $person,]);
//    }

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

    public function addMovie($user, $movieId, $locale): Movie
    {
        $userMovie = $this->movieRepository->findOneBy(['movieDbId' => $movieId]);

        if (!$userMovie) {
            $standing = $this->tmdbService->getMovie($movieId, $locale);
            $movieDetail = json_decode($standing, true);

            $userMovie = new Movie();
            $userMovie->setTitle($movieDetail['title']);
            $userMovie->setOriginalTitle($movieDetail['original_title']);
            $userMovie->setPosterPath($movieDetail['poster_path']);
            $userMovie->setReleaseDate($movieDetail['release_date']);
            $userMovie->setMovieDbId($movieDetail['id']);
            $userMovie->setRuntime($movieDetail['runtime']);
        }
        $userMovie->addUser($user);
        $this->movieRepository->add($userMovie, true);

        return $userMovie;
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
            $this->movieRepository->add($userMovie, true);
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
}

<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Creator;
use App\Entity\Genre;
use App\Entity\ImageConfig;
use App\Entity\Movie;
use App\Entity\MovieCollection;
use App\Entity\Network;
use App\Entity\ProductionCountry;
use App\Entity\SpokenLanguage;
use App\Entity\Status;
use App\Entity\TvEpisodeToAir;
use App\Entity\TvSeason;
use App\Entity\TvShow;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(UserPasswordHasherInterface $upHasher)
    {
        $this->hasher = $upHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->addConfig($manager);
        $this->addGenres($manager);
        $this->addStatus($manager);
        $this->addCompanies($manager);
        $this->addCountries($manager);
        $this->addLanguages($manager);

        $this->addMovies($manager);

        $this->addAdmin($manager);
    }

    private function addConfig(ObjectManager $manager)
    {
        $config = [
            "base_url" => "http://image.tmdb.org/t/p/",
            "secure_base_url" => "https://image.tmdb.org/t/p/",
            "backdrop_sizes" => [
                "w300",
                "w780",
                "w1280",
                "original"
            ],
            "logo_sizes" => [
                "w45",
                "w92",
                "w154",
                "w185",
                "w300",
                "w500",
                "original"
            ],
            "poster_sizes" => [
                "w92",
                "w154",
                "w185",
                "w342",
                "w500",
                "w780",
                "original"
            ],
            "profile_sizes" => [
                "w45",
                "w185",
                "h632",
                "original"
            ],
            "still_sizes" => [
                "w92",
                "w185",
                "w300",
                "original"
            ]
        ];

        $imageConfig = new ImageConfig();
        $imageConfig->setBaseUrl($config["base_url"]);
        $imageConfig->setSecureBaseUrl($config["secure_base_url"]);
        $imageConfig->setBackdropSizes($config["backdrop_sizes"]);
        $imageConfig->setLogoSizes($config["logo_sizes"]);
        $imageConfig->setPosterSizes($config["poster_sizes"]);
        $imageConfig->setProfileSizes($config["profile_sizes"]);
        $imageConfig->setStillSizes($config["still_sizes"]);

        $manager->persist($imageConfig);
        $manager->flush();
    }

    private function addGenres(ObjectManager $manager)
    {
        $genres = [
            [28, "Action"],
            [12, "Adventure"],
            [16, "Animation"],
            [35, "Comedy"],
            [80, "Crime"],
            [99, "Documentary"],
            [18, "Drama"],
            [10751, "Family"],
            [14, "Fantasy"],
            [36, "History"],
            [27, "Horror"],
            [10402, "Music"],
            [9648, "Mystery"],
            [10749, "Romance"],
            [878, "Science Fiction"],
            [10770, "TV Movie"],
            [53, "Thriller"],
            [10752, "War"],
            [37, "Western"],
        ];

        $count = count($genres);

        for ($i = 0; $i < $count; $i++) {
            $genre = new Genre();
            $genre->setGenreId($genres[$i][0]);
            $genre->setName($genres[$i][1]);
            $manager->persist($genre);
        }

        $manager->flush();
    }

    private function addStatus(ObjectManager $manager)
    {
        $statusList = ['Rumored', 'Planned', 'In Production', 'Post Production', 'Released', 'Canceled'];

        $count = count($statusList);

        for ($i = 0; $i < $count; $i++) {
            $status = new Status();
            $status->setName($statusList[$i]);
            $manager->persist($status);
        }

        $manager->flush();
    }

    private function addCompanies(ObjectManager $manager)
    {
        $production_companies = [
            ["id" => 923,
                "logo_path" => "/5UQsZrfbfG2dYJbx8DxfoTr2Bvu.png",
                "name" => "Legendary Pictures",
                "origin_country" => "US"],
            ["id" => 9996,
                "logo_path" => "/3tvBqYsBhxWeHlu62SIJ1el93O7.png",
                "name" => "Syncopy",
                "origin_country" => "GB"],
            ["id" => 13769,
                "logo_path" => "",
                "name" => "Lynda Obst Productions",
                "origin_country" => ""]
        ];

        $count = count($production_companies);

        for ($i = 0; $i < $count; $i++) {
            $company = new Company();
            $company->setCompanyId($production_companies[$i]["id"]);
            $company->setLogoPath($production_companies[$i]["logo_path"]);
            $company->setName($production_companies[$i]["name"]);
            $company->setOriginCountry($production_companies[$i]["origin_country"]);
            $manager->persist($company);
        }

        $manager->flush();
    }

    private function addCountries(ObjectManager $manager)
    {
        $production_countries = [
            ["iso_3166_1" => "GB",
                "name" => "United Kingdom"],
            ["iso_3166_1" => "US",
                "name" => "United States of America"]
        ];
        $count = count($production_countries);

        for ($i = 0; $i < $count; $i++) {
            $country = new ProductionCountry();
            $country->setIso31661($production_countries[$i]["iso_3166_1"]);
            $country->setName($production_countries[$i]["name"]);
            $manager->persist($country);
        }

        $manager->flush();
    }

    private function addLanguages(ObjectManager $manager)
    {
        $spoken_languages = [
            ["english_name" => "English",
                "iso_639_1" => "en",
                "name" => "English"]
        ];
        $count = count($spoken_languages);

        for ($i = 0; $i < $count; $i++) {
            $language = new SpokenLanguage();
            $language->setIso6391($spoken_languages[$i]["iso_639_1"]);
            $language->setName($spoken_languages[$i]["name"]);
            $language->setEnglishName($spoken_languages[$i]["english_name"]);
            $manager->persist($language);
        }

        $manager->flush();
    }

    private function addMovies(ObjectManager $manager)
    {
        $movies = [
            ["adult" => false,
                "backdrop_path" => "/rAiYTfKGqDCRIIqo664sY9XZIvQ.jpg",
                "belongs_to_collection" => null,
                "budget" => 165000000,
                "genres" => [
                    ["id" => 12],
                    ["id" => 18],
                    ["id" => 878]],
                "homepage" => "https://interstellar.withgoogle.com/",
                "id" => 157336,
                "imdb_id" => "tt0816692",
                "original_language" => "en",
                "original_title" => "Interstellar",
                "overview" => "Dans un futur proche, face à une Terre exsangue, un groupe d’explorateurs utilise un vaisseau interstellaire pour franchir un trou de ver permettant de parcourir des distances jusque‐là infranchissables. Leur but : trouver un nouveau foyer pour l’humanité.",
                "popularity" => 158.447,
                "poster_path" => "/1pnigkWWy8W032o9TKDneBa3eVK.jpg",
                "production_companies" => [
                    ["id" => 923],
                    ["id" => 9996],
                    ["id" => 13769],
                ],
                "production_countries" => [
                    ["iso_3166_1" => "GB"],
                    ["iso_3166_1" => "US"],
                ],
                "release_date" => "2014-11-05",
                "revenue" => 701729206,
                "runtime" => 169,
                "spoken_languages" => [
                    ["iso_639_1" => "en"],
                ],
                "status" => "Released",
                "tagline" => "L’Homme est né sur Terre, rien ne l’oblige à y mourir.",
                "title" => "Interstellar",
                "video" => false,
                "vote_average" => 8.4,
                "vote_count" => 27762
            ],
            ["adult" => false,
                "backdrop_path" => "/jtVl3nN5bJ4t7pgakLfGJmOrqZm.jpg",
                "belongs_to_collection" => [
                    "id" => 726871,
                    "name" => "Dune - Saga",
                    "poster_path" => "/c1AiZTXyyzmPOlTLSubp7CEeYj.jpg",
                    "backdrop_path" => "/iCFFmXkK5FdIzqZyyQQEdpkTo8C.jpg"
                ],
                "budget" => 165000000,
                "genres" => [
                    ["id" => 878,
                        "name" => "Science-Fiction"],
                    ["id" => 12,
                        "name" => "Aventure"]
                ],
                "homepage" => "https://www.dunemovie.com/",
                "id" => 438631,
                "imdb_id" => "tt1160419",
                "original_language" => "en",
                "original_title" => "Dune",
                "overview" => "L'histoire de Paul Atreides, jeune homme aussi doué que brillant, voué à connaître un destin hors du commun qui le dépasse totalement. Car, s'il veut préserver l'avenir de sa famille et de son peuple, il devra se rendre sur Dune, la planète la plus dangereuse de l'Univers. Mais aussi la seule à même de fournir la ressource la plus précieuse capable de décupler la puissance de l'Humanité. Tandis que des forces maléfiques se disputent le contrôle de cette planète, seuls ceux qui parviennent à dominer leur peur pourront survivre…",
                "popularity" => 529.791,
                "poster_path" => "/qpyaW4xUPeIiYA5ckg5zAZFHvsb.jpg",
                "production_companies" => [[
                    "id" => 923,
                    "logo_path" => "/5UQsZrfbfG2dYJbx8DxfoTr2Bvu.png",
                    "name" => "Legendary Pictures",
                    "origin_country" => "US"
                ]],
                "production_countries" => [[
                    "iso_3166_1" => "US",
                    "name" => "United States of America"
                ]],
                "release_date" => "2021-09-15",
                "revenue" => 399000000,
                "runtime" => 155,
                "spoken_languages" => [
                    ["english_name" => "Mandarin",
                        "iso_639_1" => "zh",
                        "name" => "普通话"],
                    ["english_name" => "English",
                        "iso_639_1" => "en",
                        "name" => "English"]
                ],
                "status" => "Released",
                "tagline" => "Au-delà de la peur, le destin attend.",
                "title" => "Dune",
                "video" => false,
                "vote_average" => 7.9,
                "vote_count" => 6158
            ],
            ["adult" => false,
                "backdrop_path" => "/5wJ2tckpvwcxGCAgZiccodwEJpf.jpg",
                "belongs_to_collection" => null,
                "budget" => 40000000,
                "genres" => [
                    ["id" => 28,
                        "name" => "Action"],
                    ["id" => 878,
                        "name" => "Science-Fiction"],
                    ["id" => 12,
                        "name" => "Aventure"]
                ],
                "homepage" => "",
                "id" => 841,
                "imdb_id" => "tt0087182",
                "original_language" => "en",
                "original_title" => "Dune",
                "overview" => "En l'an 10191, la substance la plus importante est l'Épice. Elle ne se trouve que sur une seule planète, Arakis, connue aussi sous le nom de Dune. La famille Atréide vient à gouverner cette planète mais son ennemi, la dynastie des Harkonnen lui tend un piège dès son arrivée. Paul, le fils du Duc Leto Atréide se réfugie alors dans le désert avec sa mère et y rencontre les Fremens, peuple caché dans le désert attendant l'arrivée d'un Messie...",
                "popularity" => 51.959,
                "poster_path" => "/nCFApKqbqRDdGc3YylVf3VsTHcg.jpg",
                "production_companies" => [
                    ["id" => 10308,
                        "logo_path" => null,
                        "name" => "Dino De Laurentiis Company",
                        "origin_country" => "US"]
                ],
                "production_countries" => [
                    ["iso_3166_1" => "US",
                        "name" => "United States of America"]
                ],
                "release_date" => "1984-12-14",
                "revenue" => 30925690,
                "runtime" => 140,
                "spoken_languages" => [
                    ["english_name" => "English",
                        "iso_639_1" => "en",
                        "name" => "English"]
                ],
                "status" => "Released",
                "tagline" => "Un monde au-delà de vos rêves. Un film au-delà de votre imagination.",
                "title" => "Dune",
                "video" => false,
                "vote_average" => 6.2,
                "vote_count" => 2120
            ]];

        $count = count($movies);
        $repoG = $manager->getRepository(Genre::class);
        $repoM = $manager->getRepository(MovieCollection::class);
        $repoC = $manager->getRepository(Company::class);
        $repoP = $manager->getRepository(ProductionCountry::class);
        $repoL = $manager->getRepository(SpokenLanguage::class);
        $repoS = $manager->getRepository(Status::class);

        for ($i = 0; $i < $count; $i++) {
            $movie = new Movie();
            $movie->setAdult($movies[$i]["adult"]);
            $movie->setBackdropPath($movies[$i]["backdrop_path"]);
            if ($movies[$i]['belongs_to_collection']) {
                $movieCollection = $repoM->findOneBy(['collection_id' => $movies[$i]['belongs_to_collection']['id']]);
                if ($movieCollection == null) {
                    $movieCollection = new MovieCollection();
                    $movieCollection->setCollectionId($movies[$i]['belongs_to_collection']['id']);
                    $movieCollection->setName($movies[$i]['belongs_to_collection']['name']);
                    $movieCollection->setPosterPath($movies[$i]['belongs_to_collection']['poster_path']);
                    $movieCollection->setBackdropPath($movies[$i]['belongs_to_collection']['backdrop_path']);
                    $manager->persist($movieCollection);
                }
                $movie->addBelongsToCollection($movieCollection);
            }
            $movie->setBudget($movies[$i]["budget"]);
            for ($j = 0; $j < count($movies[$i]["genres"]); $j++) {
                $genre = $repoG->findOneBy(['genre_id' => $movies[$i]["genres"][$j]["id"]]);
                $movie->addGenre($genre);
            }
            $movie->setHomepage($movies[$i]["homepage"]);
            $movie->setMovieDbId($movies[$i]["id"]);
            $movie->setImdbId($movies[$i]["imdb_id"]);
            $movie->setOriginalLanguage($movies[$i]["original_language"]);
            $movie->setOriginalTitle($movies[$i]["original_title"]);
            $movie->setOverview($movies[$i]["overview"]);
            $movie->setPopularity($movies[$i]["popularity"]);
            $movie->setPosterPath($movies[$i]["poster_path"]);
            for ($j = 0; $j < count($movies[$i]["production_companies"]); $j++) {
                $company = $repoC->findOneBy(['company_id' => $movies[$i]["production_companies"][$j]["id"]]);
                if ($company == null) {
                    $company = new Company();
                    $company->setCompanyId($movies[$i]["production_companies"][$j]["id"]);
                    $company->setLogoPath($movies[$i]["production_companies"][$j]["logo_path"]);
                    $company->setName($movies[$i]["production_companies"][$j]["name"]);
                    $company->setOriginCountry($movies[$i]["production_companies"][$j]["origin_country"]);
                    $manager->persist($company);
                }
                $movie->addProductionCompany($company);
            }
            for ($j = 0; $j < count($movies[$i]["production_countries"]); $j++) {

                $country = $repoP->findOneBy(['iso_3166_1' => $movies[$i]["production_countries"][$j]["iso_3166_1"]]);
                $movie->addProductionCountry($country);
            }
            $movie->setReleaseDate(\DateTime::createFromFormat("Y-m-d", $movies[$i]["release_date"], null));
            $movie->setRevenue($movies[$i]["revenue"]);
            $movie->setRuntime($movies[$i]["runtime"]);
            for ($j = 0; $j < count($movies[$i]["spoken_languages"]); $j++) {

                $language = $repoL->findOneBy(['iso_639_1' => $movies[$i]["spoken_languages"][$j]["iso_639_1"]]);
                if ($language == null) {
                    $language = new SpokenLanguage();
                    $language->setEnglishName($movies[$i]["spoken_languages"][$j]['english_name']);
                    $language->setIso6391($movies[$i]["spoken_languages"][$j]['iso_639_1']);
                    $language->setName($movies[$i]["spoken_languages"][$j]['name']);
                    $manager->persist($language);
                }
                $movie->addSpokenLanguage($language);
            }
            $status = $repoS->findOneBy(['name' => $movies[$i]["status"]]);
            $movie->setStatus($status);
            $movie->setTagline($movies[$i]["tagline"]);
            $movie->setTitle($movies[$i]["title"]);
            $movie->setVideo($movies[$i]["video"]);
            $movie->setVoteAverage($movies[$i]["vote_average"]);
            $movie->setVoteCount($movies[$i]["vote_count"]);

            $manager->persist($movie);
        }

        $manager->flush();
    }

    private function addTvShows(ObjectManager $manager)
    {

        $tvShows = ["adult" => false,
            "backdrop_path" => "/yVZESlkDTihnGB0qa9MLWCop9Xf.jpg",
            "created_by" => [
                ["id" => 1456588,
                    "credit_id" => "602c6fb3223e20003f95c70e",
                    "name" => "Darío Madrona",
                    "gender" => 2,
                    "profile_path" => null]
            ],
            "episode_run_time" => [
                60
            ],
            "first_air_date" => "2021-10-07",
            "genres" => [
                ["id" => 9648,
                    "name" => "Mystère"],
                ["id" => 18,
                    "name" => "Drame"]
            ],
            "homepage" => "https=>//www.peacocktv.com/stream-tv/one-of-us-is-lying",
            "id" => 118958,
            "in_production" => true,
            "languages" => [
                "en"
            ],
            "last_air_date" => "2021-10-21",
            "last_episode_to_air" =>
                ["air_date" => "2021-10-21",
                    "episode_number" => 8,
                    "id" => 3241298,
                    "name" => "",
                    "overview" => "",
                    "production_code" => "",
                    "season_number" => 1,
                    "still_path" => "/cuoUndYBim95oK0cYJJUvKxl7Yi.jpg",
                    "vote_average" => 9.0,
                    "vote_count" => 1],
            "name" => "Qui ment ?",
            "next_episode_to_air" => null,
            "networks" => [
                ["name" => "Peacock",
                    "id" => 3353,
                    "logo_path" => "/gIAcGTjKKr0KOHL5s4O36roJ8p7.png",
                    "origin_country" => "US"]
            ],
            "number_of_episodes" => 8,
            "number_of_seasons" => 2,
            "origin_country" => [
                "US"
            ],
            "original_language" => "en",
            "original_name" => "One of Us Is Lying",
            "overview" => "Quand un lycéen qui s'apprêtait à dévoiler des secrets croustillants sur ses camarades décède, les quatre adolescents qui étaient à ses côtés au moment du drame deviennent de potentiels suspects. Qui est coupable ? Qui ment ? Une chose est sûre, tout le monde a quelque chose à cacher...",
            "popularity" => 108.532,
            "poster_path" => "/hKMpbHY7xqFKaBR8W7jE61JlQB6.jpg",
            "production_companies" => [
                ["id" => 7938,
                    "logo_path" => "/8I52qpy2Dp48gD9Jf6W1D6E7Imo.png",
                    "name" => "UCP",
                    "origin_country" => "US"],
                ["id" => 64716,
                    "logo_path" => null,
                    "name" => "Five More Minutes Productions",
                    "origin_country" => ""]
            ],
            "production_countries" => [
                ["iso_3166_1" => "US",
                    "name" => "United States of America"]
            ],
            "seasons" => [
                ["air_date" => "2021-10-07",
                    "episode_count" => 8,
                    "id" => 181989,
                    "name" => "Saison 1",
                    "overview" => "",
                    "poster_path" => "/hKMpbHY7xqFKaBR8W7jE61JlQB6.jpg",
                    "season_number" => 1],
                ["air_date" => null,
                    "episode_count" => 0,
                    "id" => 244767,
                    "name" => "Saison 2",
                    "overview" => "",
                    "poster_path" => null,
                    "season_number" => 2]
            ],
            "spoken_languages" => [
                ["english_name" => "English",
                    "iso_639_1" => "en",
                    "name" => "English"]
            ],
            "status" => "Returning Series",
            "tagline" => "Cinq entrent en retenue, seuls quatre en sortent vivants.",
            "type" => "Miniseries",
            "vote_average" => 7.0,
            "vote_count" => 26
        ];

        $count = count($tvShows);
        $repoC = $manager->getRepository(Creator::class);
        $repoG = $manager->getRepository(Genre::class);
        $repoT = $manager->getRepository(TvEpisodeToAir::class);
        $repoN = $manager->getRepository(Network::class);
        $repoP = $manager->getRepository(Company::class);
        $repoPc = $manager->getRepository(ProductionCountry::class);
        $repoS = $manager->getRepository(TvSeason::class);
        $repoL = $manager->getRepository(SpokenLanguage::class);
        $repoSt = $manager->getRepository(Status::class);

        for ($i = 0; $i < $count; $i++) {

            $tv = $tvShows[$i];

            $tvShow = new TvShow();

            $tvShow->setAdult($tv['adult']);
            $creators = $tv['created_by'];
            for ($j = 0; $j < count($creators); $j++) {
                $creator = $creators[$j];
                $created_by = $repoC->findOneBy(['created_by' => $creator['created_by']]);
                if ($created_by == null) {
                    $created_by = new Creator();
                    $created_by->setCreatorId($creator['id']);
                    $created_by->setCreditId($creator['credit_id']);
                    $created_by->setName($creator['name']);
                    $created_by->setGender($creator['gender']);
                    $created_by->setProfilePath($creator['profile_path']);
                    $manager->persist($created_by);
                }
                $tvShow->addCreatedBy($created_by);
            }
            $tvShow->setEpisodeRunTime($tv['episode_run_time']);
            $tvShow->setFirstAirDate(\DateTime::createFromFormat("Y-m-d", $tv['first_air_date'], null));
            for ($j = 0; $j < count($tv["genres"]); $j++) {
                $genre = $repoG->findOneBy(['genre_id' => $tv['genres'][$j]['id']]);
                $tvShow->addGenre($genre);
            }
            $tvShow->setHomepage($tv['homepage']);
            $tvShow->setTvShowId($tv['id']);
            $tvShow->setInProduction(($tv['in_production']));
            $tvShow->setLanguages($tv['languages']);
            $tvShow->setLastAirDate(\DateTime::createFromFormat("Y-m-d", $tv['first_air_date'], null));
            if ($tv['last_episode_to_air']) {
                $last = $tv['last_episode_to_air'];
                $lastEpisodeToAir = $repoT->findOneBy(['episode_to_air_id', $last['id']]);
                if ($lastEpisodeToAir == null) {
                    $lastEpisodeToAir = new TvEpisodeToAir();
                    $lastEpisodeToAir->setAirDate(\DateTime::createFromFormat("Y-m-d", $last['last_air_date'], null));
                    $lastEpisodeToAir->setEpisodeNumber($last['episode_number']);
                    $lastEpisodeToAir->setEpisodeToAirId($last['id']);
                    $lastEpisodeToAir->setName($last['name']);
                    $lastEpisodeToAir->setOverview($last['overview']);
                    $lastEpisodeToAir->setProductionCode($last['production_code']);
                    $lastEpisodeToAir->setSeasonNumber($last['season_number']);
                    $lastEpisodeToAir->setStillPath($last['still_path']);
                    $lastEpisodeToAir->setVoteAverage($last['vote_average']);
                    $lastEpisodeToAir->setVoteCount($last['vote_count']);
                    $manager->persist($lastEpisodeToAir);
                }
                $tvShow->setLastEpisodeToAir($lastEpisodeToAir);
            }
            $tvShow->setName($tv['name']);
            $networks = $tv['networks'];
            for ($j = 0; $j < count($networks); $j++) {
                $network = $repoN->findOneBy(['network_id' => $networks[$j]['id']]);
                if ($network == null) {
                    $network = new Network();
                    $network->setName($networks[$j]['name']);
                    $network->setNetworkId($networks[$j]['id']);
                    $network->setLogoPath($networks[$j]['logo_path']);
                    $network->setOriginCountry($networks[$j]['origin_country']);
                    $manager->persist($network);
                }
                $tvShow->addNetwork($network);
            }
            $tvShow->setNumberOfEpisodes($tv['number_of_episodes']);
            $tvShow->setNumberOfSeasons($tv['number_of_seasons']);
            $tvShow->setOriginCountry($tv['origin_country']);
            $tvShow->setOriginalLanguage($tv['original_language']);
            $tvShow->setOriginalName($tv['original_name']);
            $tvShow->setOverview($tv['overview']);
            $tvShow->setPopularity($tv['popularity']);
            $tvShow->setPosterPath($tv['poster_path']);
            $companies = $tv['production_companies'];
            for ($j = 0; $j < count($companies); $j++) {
                $company = $repoP->findOneBy(['company_id' => $companies[$j]['id']]);
                if ($company == null) {
                    $company = new Company();
                    $company->setCompanyId($companies[$j]['id']);
                    $company->setLogoPath($companies[$j]['logo_path']);
                    $company->setName($companies[$j]['name']);
                    $company->setOriginCountry($companies[$j]['origin_country']);
                    $manager->persist($company);
                }
                $tvShow->addProductionCompany($company);
            }
            $countries = $tv['production_countries'];
            for ($j = 0; $j < count($countries); $j++) {
                $country = $repoPc->findOneBy(['iso_3166_1' => $countries[$j]['iso_3166_1']]);
                if ($country == null) {
                    $country = new ProductionCountry();
                    $country->setIso31661($countries[$j]['iso_3166_1']);
                    $country->setName($countries[$j]['name']);
                    $manager->persist($country);
                }
                $tvShow->addProductionCountry($country);
            }
            $seasons = $tvShow['seasons'];
            for ($j = 0; $j < count($seasons); $j++) {
                $season = $repoS->findOneBy(['season_id'], $seasons[$j]['id']);
                if ($season == null) {
                    $season = new TvSeason();
                    $season->setAirDate(\DateTime::createFromFormat("Y-m-d", $seasons[$j]['air_date']));
                    $season->setEpisodeCount($seasons[$j]['episode_count']);
                    $season->setSeasonId($seasons[$j]['id']);
                    $season->setName($seasons[$j]['name']);
                    $season->setOverview($seasons[$j]['overview']);
                    $season->setPosterPath($seasons[$j]['poster_path']);
                    $season->setSeasonNumber($seasons[$j]['season_number']);
                    $manager->persist($season);
                }
                $tvShow->addSeason($season);
            }
            $spokenLanguages = $tv['spoken_languages'];
            for ($j = 0; $j < count($spokenLanguages); $j++) {
                $spokenLanguage = $repoPc->findOneBy(['iso_639_1' => $countries[$j]['iso_639_1']]);
                if ($spokenLanguage == null) {
                    $spokenLanguage = new SpokenLanguage();
                    $spokenLanguage->setEnglishName($spokenLanguages[$j]['english_name']);
                    $spokenLanguage->setIso6391($spokenLanguages[$j]['iso_639_1']);
                    $spokenLanguage->setName($spokenLanguages[$j]['name']);
                    $manager->persist($spokenLanguage);
                }
                $tvShow->addSpokenLanguage($spokenLanguage);
            }
            $status = $repoSt->findOneBy(['name' => $tv['status']]);
            if ($status == null) {
                $status = new Status();
                $status->setName($tv['status']);
                $manager->persist($status);
            }
            $tvShow->addStatus($status);
            $tvShow->setTagline($tv['tagline']);
            $tvShow->setTvShowType($tv['type']);
            $tvShow->setVoteAverage($tv['vote_average']);
            $tvShow->setVoteCount($tv['vote_count']);

            $manager->persist($tvShow);
        }
    }

    private function addAdmin(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail("ojm16@free.fr");
        $password = $this->hasher->hashPassword($user, 'a123-B456-c789');
        $user->setPassword($password);
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $user);
    }
}

<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class TMDBService
{
    // Clé d'API (v3 auth)
    //      f7e3c5fe794d565b471334c9c5ecaf96
    // Jeton d'accès en lecture à l'API (v4 auth)
    //      eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJmN2UzYzVmZTc5NGQ1NjViNDcxMzM0YzljNWVjYWY5NiIsInN1YiI6IjYyMDJiZjg2ZTM4YmQ4MDA5MWVjOWIzOSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.9-8i4TOkKXtPZE_nkXk1ZvAlbDYgAdtcrCR6R8Dv3Wg

    private HttpClientInterface $client;
    private string $api_key = "f7e3c5fe794d565b471334c9c5ecaf96";

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function trending($mediaType, $timeWindow, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/trending/' . $mediaType . '/' . $timeWindow . '?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function discoverMovies($page, $sort, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/discover/movie?api_key=' . $this->api_key . '&language=' . $locale . '&sort_by=' . $sort . '&include_adult=false&include_video=false&page=' . $page . '&with_watch_monetization_types=flatrate',
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function moviesByGenres($page, $genres, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/discover/movie?api_key=' . $this->api_key . '&language=' . $locale . '&sort_by=popularity.desc&include_adult=false&include_video=false&page=' . $page . '&with_genres=' . $genres . '&with_watch_monetization_types=flatrate'
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function moviesByDate($page, $date, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/discover/movie?api_key=' . $this->api_key . '&language=' . $locale . '&sort_by=popularity.desc&include_adult=false&include_video=false&page=' . $page . '&primary_release_year=' . $date . '&with_watch_monetization_types=flatrate'
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function moviesSearch($page, $query, $year, $locale): ?string
    {
        try {
            if (strlen($year)) {
                $response = $this->client->request(
                    'GET',
                    'https://api.themoviedb.org/3/search/movie?api_key=' . $this->api_key . '&language=' . $locale . '&page=' . $page . '&query=' . $query . '&year=' . $year . '&include_adult=false'
                );
            } else {
                $response = $this->client->request(
                    'GET',
                    'https://api.themoviedb.org/3/search/movie?api_key=' . $this->api_key . '&language=' . $locale . '&page=' . $page . '&query=' . $query . '&include_adult=false'
                );
            }
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getGenres($locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/genre/movie/list?api_key=' . $this->api_key . '&language=' . $locale
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getMovie($movieId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $movieId . '?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getMovieCollection($collectionId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/collection/' . $collectionId . '?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getMovieCredits($movieId, $locale): ?string
    {
        $noCredits = json_encode(["id" => $movieId, "cast" => [], "crew" => []]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $movieId . '/credits?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noCredits;
            }
        } catch (Throwable $e) {
            return $noCredits;
        }
    }

    public function getTv($showId, $locale, $details = null): ?string
    {
        $request = 'https://api.themoviedb.org/3/tv/' . $showId . '?api_key=' . $this->api_key . '&language=' . $locale;
        if ($details) {
            $request .= '&append_to_response=';
            foreach ($details as $detail) {
                $request .= $detail . ',';
            }
        }
        try {
            $response = $this->client->request(
                'GET',
                $request,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getTvCredits($tvId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/credits?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return 'Response : ' . $exception->getMessage() . ' - code : ' . $exception->getCode();
            }
        } catch (Throwable $exception) {
            return 'Request : ' . $exception->getMessage() . ' - code : ' . $exception->getCode();
        }
    }

    public function getTvKeywords($tvId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/keywords?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getTvWatchProviders($tvId): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/watch/providers?api_key=' . $this->api_key,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getTvSimilar($tvId): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/similar?api_key=' . $this->api_key,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function search($page, $query, $year, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/search/tv?api_key=' . $this->api_key . '&page=' . $page . '&language=' . $locale . '&query=' . $query . '&include_adult=false' . ($year ? ('&first_air_date_year=' . $year) : ''),
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getLatest($locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/latest?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getSeries($kind, $page, $locale, $timezone = null): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $kind . '?api_key=' . $this->api_key . '&language=' . $locale . '&page=' . $page . ($timezone?'&timezone='.$timezone:''),
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                return "";
            }
        } catch (Throwable $exception) {
            return "";
        }
    }

    public function getTvSeason($tvId, $seasonNumber, $locale, $details = null): ?string
    {
        $request = 'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '?api_key=' . $this->api_key . '&language=' . $locale;
        if ($details) {
            $request .= '&append_to_response=';
            foreach ($details as $detail) {
                $request .= $detail . ',';
            }
        }
        try {
            $response = $this->client->request(
                'GET',
                $request,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getTvSeasonCredits($tvId, $seasonNumber, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '/credits?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getTvEpisode($tvId, $seasonNumber, $episodeNumber, $locale, $details = null): ?string
    {
        $request = 'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '/episode/' . $episodeNumber . '?api_key=' . $this->api_key . '&language=' . $locale;
        if ($details) {
            $request .= '&append_to_response=';
            foreach ($details as $detail) {
                $request .= $detail . ',';
            }
        }
        try {
            $response = $this->client->request(
                'GET',
                $request,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getTvEpisodeCredits($tvId, $seasonNumber, $episodeNumber, $locale): ?string
    {
        $request = 'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '/episode/' . $episodeNumber . '/credits?api_key=' . $this->api_key . '&language=' . $locale;
        try {
            $response = $this->client->request(
                'GET',
                $request,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getMovieImages($id, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $id . '/images?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getTvImages($tvId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $tvId . '/images?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return "";
            }
        } catch (Throwable $e) {
            return "";
        }
    }

    public function getMovieRecommendations($movieId, $locale): ?string
    {
        $zeroRecommandations = json_encode(["page" => 1, "results" => [], "total_pages" => 0, "total_results" => 40]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $movieId . '/recommendations?api_key=' . $this->api_key . '&language=' . $locale . '&page=1' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $zeroRecommandations;
            }
        } catch (Throwable $e) {
            return $zeroRecommandations;
        }
    }

    public function getMovieReleaseDates($movieId): ?string
    {
        $noReleaseDates = json_encode(["id" => $movieId, "results" => []]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $movieId . '/release_dates?api_key=' . $this->api_key,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noReleaseDates;
            }
        } catch (Throwable $e) {
            return $noReleaseDates;
        }
    }

    public function getCountries(): ?string
    {
        $noCountries = json_encode([]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/configuration/countries?api_key=' . $this->api_key,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noCountries;
            }
        } catch (Throwable $e) {
            return $noCountries;
        }
    }

    public function getWatchProviders($movieId): ?string
    {
        $noWatchProviders = json_encode([]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/movie/' . $movieId . '/watch/providers?api_key=' . $this->api_key,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noWatchProviders;
            }
        } catch (Throwable $e) {
            return $noWatchProviders;
        }
    }

    public function getPopularPeople($locale): ?string
    {
        $noPopularPeople = json_encode([]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/person/popular?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noPopularPeople;
            }
        } catch (Throwable $e) {
            return $noPopularPeople;
        }
    }

    public function getPerson($id, $locale, $images = false): ?string
    {
        $noOne = json_encode([]);
        try {
            if ($images) {
                $response = $this->client->request(
                    'GET',
                    'https://api.themoviedb.org/3/person/' . $id . '?api_key=' . $this->api_key . '&language=' . $locale . '&append_to_response=images'
                );
            } else {
                $response = $this->client->request(
                    'GET',
                    'https://api.themoviedb.org/3/person/' . $id . '?api_key=' . $this->api_key . '&language=' . $locale
                );
            }
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noOne;
            }
        } catch (Throwable $e) {
            return $noOne;
        }
    }

    public function getPersonCredits($id, $locale): ?string
    {
        $noCredits = json_encode([]);
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/person/' . $id . '/combined_credits?api_key=' . $this->api_key . '&language=' . $locale
            );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                return $noCredits;
            }
        } catch (Throwable $e) {
            return $noCredits;
        }
    }
}

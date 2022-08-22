<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class CallTmdbService
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function discoverMovies($page, $sort, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/discover/movie?api_key='.$this->api_key.'&language=' . $locale . '&sort_by=' . $sort . '&include_adult=false&include_video=false&page=' . $page . '&with_watch_monetization_types=flatrate',
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function moviesByGenres($page, $genres, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/discover/movie?api_key='.$this->api_key.'&language=' . $locale . '&sort_by=popularity.desc&include_adult=false&include_video=false&page=' . $page . '&with_genres=' . $genres . '&with_watch_monetization_types=flatrate'
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function moviesByDate($page, $date, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/discover/movie?api_key='.$this->api_key.'&language=' . $locale . '&sort_by=popularity.desc&include_adult=false&include_video=false&page=' . $page . '&primary_release_year=' . $date . '&with_watch_monetization_types=flatrate'
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function moviesSearch($page, $query, $year, $locale): ?string
    {
        if ($year != 'none') {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/search/movie?api_key='.$this->api_key.'&language=' . $locale . '&page=' . $page . '&query=' . $query . '&year=' . $year . '&include_adult=false'
            );
        } else {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/search/movie?api_key='.$this->api_key.'&language=' . $locale . '&page=' . $page . '&query=' . $query . '&include_adult=false'
            );
        }
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getGenres($locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/genre/movie/list?api_key='.$this->api_key.'&language=' . $locale
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
/*    public function discoverTV($page, $locale): ?string
    {
        //
        // https://www.themoviedb.org/tv/81322-the-time-traveler-s-wife
        // Série "The Time Traveler's Wife" TMDB.TV
        // TODO
        //   Get Series from TMDB !!!

        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/discover/tv?api_key='.$this->api_key.'&language=' . $locale . '&sort_by=release_date.desc&page=' . $page . '&timezone=Europe%2FParis&include_null_first_air_dates=false&watch_region=FR&with_watch_monetization_types=flatrate&with_status=0&with_type=0',
        );
        return $response->getContent();
    }*/

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMovie($movieId, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $movieId . '?api_key='.$this->api_key.'&language=' . $locale,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMovieCollection($collectionId, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/collection/' . $collectionId . '?api_key='.$this->api_key.'&language=' . $locale,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMovieCredits($movieId, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $movieId . '/credits?api_key='.$this->api_key.'&language=' . $locale,
        );
        return $response->getContent();
    }

    public function getTv($showId, $locale): ?string
    {
        try {
            $response = $this->client->request(
                'GET',
                'https://api.themoviedb.org/3/tv/' . $showId . '?api_key=' . $this->api_key . '&language=' . $locale,
            );
            try {
                return $response->getContent();
            } catch (Throwable $exception) {
                dump($exception->getMessage(), $exception->getCode());
                return "";
            }
        } catch (Throwable $exception) {
            dump($exception->getMessage(), $exception->getCode());
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
                dump($exception->getMessage(), $exception->getCode());
                return "";
            }
        } catch (Throwable $exception) {
            dump($exception->getMessage(), $exception->getCode());
            return "";
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
                dump($exception->getMessage(), $exception->getCode());
                return "";
            }
        } catch (Throwable $exception) {
            dump($exception->getMessage(), $exception->getCode());
            return "";
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getTvSeason($tvId, $seasonNumber, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '?api_key='.$this->api_key.'&language=' . $locale,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getTvEpisode($tvId, $seasonNumber, $episodeNumber, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/tv/' . $tvId . '/season/' . $seasonNumber . '/episode/' . $episodeNumber . '?api_key='.$this->api_key.'&language=' . $locale,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMovieRecommendations($movieId, $locale): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $movieId . '/recommendations?api_key='.$this->api_key.'&language=' . $locale . '&page=1' . $locale,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMovieReleaseDates($movieId): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/movie/' . $movieId . '/release_dates?api_key='.$this->api_key,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getCountries(): ?string
    {
        $response = $this->client->request(
            'GET',
            'https://api.themoviedb.org/3/configuration/countries?api_key='.$this->api_key,
        );
        return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getPerson($id, $locale): ?string
    {
        dump($id, $locale);
        if ($id && $locale) {
            $response = $this->client->request('GET', 'https://api.themoviedb.org/3/person/' . $id . '?api_key='.$this->api_key.'&language=' . $locale);
            return $response->getContent();
        }
        return null;
    }
}

<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class CallImdbService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function searchName($name): ?string
    {
        try {
        $response = $this->client->request(
            'GET',
            'https://imdb-api.com/en/API/SearchName/k_twx0t466/'.$name,
        );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                dump($e);
                return "";
            }
        } catch (Throwable $e) {
            dump($e);
            return "";
        }
    }

    public function getPerson($name, $locale): ?string
    {
        try {
        $response = $this->client->request(
            'GET',
            'https://imdb-api.com/'.$locale.'/API/Name/k_twx0t466/'.$name,
        );
            try {
                return $response->getContent();
            } catch (Throwable $e) {
                dump($e);
                return "";
            }
        } catch (Throwable $e) {
            dump($e);
            return "";
        }
    }
}

